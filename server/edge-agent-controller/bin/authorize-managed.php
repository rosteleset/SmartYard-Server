<?php

declare(strict_types=1);

use SesameWare\RbtAgent\ControllerIdentity;
use SesameWare\RbtAgent\Database;
use SesameWare\RbtAgent\ManagedAuthorization;
use SesameWare\RbtAgent\Protocol;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['agent-id:', 'secret-file::', 'ttl::']);
$agentId = is_string($options['agent-id'] ?? null) ? trim($options['agent-id']) : '';
if ($agentId === '') {
    throw new RuntimeException('--agent-id is required');
}
$secretFile = is_string($options['secret-file'] ?? null) ? $options['secret-file'] : '';
if ($secretFile !== '') {
    $secret = trim((string) file_get_contents($secretFile));
} else {
    fwrite(STDERR, "Managed secret (read from stdin): ");
    $secret = trim((string) fgets(STDIN));
}
if ($secret === '') {
    throw new RuntimeException('managed secret is required');
}
$ttl = max(60, min(1800, (int) ($options['ttl'] ?? 600)));
$db = Database::connect(rbtAgentConfig());
$identity = ControllerIdentity::loadOrCreate(rbtAgentIdentityPath());
$statement = $db->prepare(
    "SELECT pairing_status, management_state FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE"
);
$db->beginTransaction();
try {
    $statement->execute(['agent_id' => $agentId]);
    $agent = $statement->fetch();
    if (!$agent || $agent['pairing_status'] !== 'active') {
        throw new RuntimeException('active paired agent not found');
    }
    $management = json_decode((string) $agent['management_state'], true, flags: JSON_THROW_ON_ERROR);
    if (!is_array($management) || ($management['enabled'] ?? false) !== true || ($management['credentialSet'] ?? false) !== true) {
        throw new RuntimeException('agent has not enabled managed authorization locally');
    }
    $revision = $management['credentialRevision'] ?? null;
    if (!is_int($revision) || $revision <= 0) {
        throw new RuntimeException('agent management credential revision is invalid');
    }
    $scopes = ManagedAuthorization::normalizeScopes(
        is_array($management['scopes'] ?? null) ? $management['scopes'] : [],
    );
    if ($scopes === []) {
        throw new RuntimeException('agent did not allow any managed scopes');
    }
    $challenge = Protocol::encodeBase64(random_bytes(32));
    $proof = ManagedAuthorization::proof(
        $secret,
        $agentId,
        $identity->id(),
        $revision,
        $challenge,
        $scopes,
    );
    $update = $db->prepare(<<<'SQL'
        UPDATE edge_agents SET
            managed_authorized = false,
            managed_scopes = '[]'::jsonb,
            management_revision = :revision,
            management_challenge = :challenge,
            management_proof = :proof,
            management_requested_scopes = CAST(:scopes AS jsonb),
            management_expires_at = now() + CAST(:ttl AS integer) * interval '1 second',
            updated_at = now()
        WHERE agent_id = :agent_id
        SQL);
    $update->execute([
        'revision' => $revision,
        'challenge' => $challenge,
        'proof' => $proof,
        'scopes' => json_encode($scopes, JSON_THROW_ON_ERROR),
        'ttl' => $ttl,
        'agent_id' => $agentId,
    ]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $error;
} finally {
    sodium_memzero($secret);
}

fwrite(STDOUT, json_encode([
    'ok' => true,
    'agentId' => $agentId,
    'revision' => $revision,
    'scopes' => $scopes,
    'expiresInSec' => $ttl,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);

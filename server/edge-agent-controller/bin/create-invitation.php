<?php

declare(strict_types=1);

use SesameWare\RbtAgent\ControllerIdentity;
use SesameWare\RbtAgent\Database;

require_once dirname(__DIR__) . '/bootstrap.php';

$options = getopt('', ['server-url:', 'ttl::', 'display-name::', 'created-by::']);
$config = rbtAgentConfig();
$serverURL = is_string($options['server-url'] ?? null) ? trim($options['server-url']) : '';
if ($serverURL === '') {
    $configured = $config['edgeAgentController']['publicUrl'] ?? $config['api']['frontend'] ?? '';
    if (is_string($configured)) {
        $parts = parse_url($configured);
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            $serverURL = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        }
    }
}
if (!preg_match('#^https://[^/]+$#', $serverURL)) {
    throw new RuntimeException('--server-url must be an HTTPS origin');
}
$ttl = max(60, min(3600, (int) ($options['ttl'] ?? 600)));
$displayName = is_string($options['display-name'] ?? null) ? trim($options['display-name']) : 'RBT';
$createdBy = isset($options['created-by']) ? (int) $options['created-by'] : null;
$identity = ControllerIdentity::loadOrCreate(rbtAgentIdentityPath());
$db = Database::connect($config);
$pairingID = 'pair_' . rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
$pairingCode = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
$expiresAt = $now->modify('+' . $ttl . ' seconds');

$statement = $db->prepare(<<<'SQL'
    INSERT INTO edge_agent_pairing_invitations (
        pairing_id, code_hash, controller_id, expires_at, created_by, metadata
    ) VALUES (
        :pairing_id, :code_hash, :controller_id, :expires_at, :created_by, CAST(:metadata AS jsonb)
    )
    SQL);
$statement->execute([
    'pairing_id' => $pairingID,
    'code_hash' => hash('sha256', $pairingCode),
    'controller_id' => $identity->id(),
    'expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
    'created_by' => $createdBy,
    'metadata' => json_encode(['displayName' => $displayName], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
]);

$invitation = [
    'schemaVersion' => 1,
    'controllerType' => 'rbt',
    'serverUrl' => $serverURL,
    'controllerId' => $identity->id(),
    'controllerPublicKey' => $identity->publicKey(),
    'controllerKeyId' => $identity->keyId(),
    'pairingCode' => $pairingCode,
    'expiresAt' => $expiresAt->format('Y-m-d\TH:i:s\Z'),
    'displayName' => $displayName,
];
fwrite(STDOUT, json_encode($invitation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL);

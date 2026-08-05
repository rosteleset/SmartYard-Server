<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

final class ControllerResponse
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers,
    ) {
    }
}

final class ControllerError extends RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

final class Controller
{
    private const SCHEMA_VERSION = 1;
    private const MAX_CLOCK_SKEW_SECONDS = 60;
    private const REPLAY_TTL_SECONDS = 300;
    private const MAX_BODY_BYTES = 4 * 1024 * 1024;

    public function __construct(
        private readonly PDO $db,
        private readonly ControllerIdentity $identity,
        private readonly JsonLog $log,
    ) {
    }

    /**
     * @param array<string,string> $headers
     */
    public function handle(
        string $method,
        string $path,
        array $headers,
        string $body,
        string $remoteAddress = '',
    ): ControllerResponse {
        $metadata = null;
        try {
            if (strtoupper($method) !== 'POST') {
                throw new ControllerError(405, 'method_not_allowed');
            }
            if (strlen($body) > self::MAX_BODY_BYTES) {
                throw new ControllerError(413, 'request_body_too_large');
            }
            $metadata = $this->metadataFromHeaders($headers, 'request', $method, $path, 0);
            if (preg_match('#^/rbt-agent/v2/actions/([A-Za-z0-9][A-Za-z0-9_.:-]{0,127})/result$#', $path, $matches)) {
                return $this->actionResult($metadata, $headers, $body, $remoteAddress, $matches[1]);
            }
            return match ($path) {
                '/rbt-agent/v2/pair' => $this->pair($metadata, $headers, $body, $remoteAddress),
                '/rbt-agent/v2/pair/confirm' => $this->confirmPairing($metadata, $headers, $body, $remoteAddress),
                '/rbt-agent/v2/sync' => $this->sync($metadata, $headers, $body, $remoteAddress),
                '/rbt-agent/v2/revoke' => $this->revoke($metadata, $headers, $body, $remoteAddress),
                default => throw new ControllerError(404, 'endpoint_not_found'),
            };
        } catch (ControllerError $error) {
            $this->log->write('request_rejected', [
                'path' => $path,
                'status' => $error->status,
                'reason' => $error->getMessage(),
                'remoteAddress' => $remoteAddress,
                'signerId' => is_array($metadata) ? ($metadata['signerId'] ?? null) : null,
            ]);
            return $this->errorResponse($method, $path, $metadata, $error->status, $error->getMessage());
        } catch (Throwable $error) {
            $this->log->write('request_failed', [
                'path' => $path,
                'reason' => $error->getMessage(),
                'remoteAddress' => $remoteAddress,
                'signerId' => is_array($metadata) ? ($metadata['signerId'] ?? null) : null,
            ]);
            return $this->errorResponse($method, $path, $metadata, 500, 'internal_error');
        }
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function pair(array $metadata, array $headers, string $rawBody, string $remoteAddress): ControllerResponse
    {
        $body = $this->decodeJSON($rawBody);
        $agent = $this->requireArray($body, 'agent');
        $agentIdentity = $this->requireArray($agent, 'identity');
        $agentId = $this->requireString($agent, 'agentId');
        $agentName = $this->requireString($agent, 'agentName');
        $publicKey = $this->requireString($agentIdentity, 'publicKey');
        $keyId = $this->requireString($agentIdentity, 'keyId');
        $pairingCode = $this->requireString($body, 'pairingCode');
        $agentChallenge = $this->requireString($body, 'agentChallenge');
        if (($body['schemaVersion'] ?? null) !== self::SCHEMA_VERSION || ($agentIdentity['algorithm'] ?? null) !== 'ed25519') {
            throw new ControllerError(400, 'invalid_pairing_schema');
        }
        if (!hash_equals($agentId, $metadata['signerId']) ||
            !hash_equals($keyId, $metadata['keyId']) ||
            !hash_equals($keyId, Protocol::keyId($publicKey))) {
            throw new ControllerError(401, 'agent_identity_mismatch');
        }
        $this->verifyRequest($metadata, $headers, $rawBody, $publicKey);

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "SELECT * FROM edge_agent_pairing_invitations WHERE code_hash = :code_hash FOR UPDATE"
            );
            $statement->execute(['code_hash' => hash('sha256', $pairingCode)]);
            $invitation = $statement->fetch();
            if (!$invitation || !in_array($invitation['status'], ['created', 'pending'], true)) {
                throw new ControllerError(403, 'pairing_invitation_invalid');
            }
            if ((new DateTimeImmutable((string) $invitation['expires_at'])) <= $this->now()) {
                $this->db->prepare(
                    "UPDATE edge_agent_pairing_invitations SET status = 'expired' WHERE pairing_id = :pairing_id"
                )->execute(['pairing_id' => $invitation['pairing_id']]);
                throw new ControllerError(403, 'pairing_invitation_expired');
            }
            if (!hash_equals($this->identity->id(), (string) $invitation['controller_id'])) {
                throw new ControllerError(409, 'pairing_controller_identity_mismatch');
            }
            if ($invitation['status'] === 'pending' &&
                (!hash_equals((string) $invitation['pending_agent_id'], $agentId) ||
                 !hash_equals((string) $invitation['pending_key_id'], $keyId))) {
                throw new ControllerError(409, 'pairing_invitation_already_pending');
            }
            $this->reserveReplay($agentId, $metadata['requestId']);
            $controllerChallenge = Protocol::encodeBase64(random_bytes(32));
            $update = $this->db->prepare(<<<'SQL'
                UPDATE edge_agent_pairing_invitations SET
                    status = 'pending',
                    pending_agent_id = :agent_id,
                    pending_agent_name = :agent_name,
                    pending_public_key = :public_key,
                    pending_key_id = :key_id,
                    pending_agent_version = :agent_version,
                    pending_capabilities = CAST(:capabilities AS jsonb),
                    agent_challenge = :agent_challenge,
                    controller_challenge = :controller_challenge,
                    pending_last_sequence = :sequence,
                    pending_at = now()
                WHERE pairing_id = :pairing_id
                SQL);
            $update->execute([
                'agent_id' => $agentId,
                'agent_name' => $agentName,
                'public_key' => $publicKey,
                'key_id' => $keyId,
                'agent_version' => is_string($agent['version'] ?? null) ? $agent['version'] : '',
                'capabilities' => $this->encodeJSON($agent['capabilities'] ?? []),
                'agent_challenge' => $agentChallenge,
                'controller_challenge' => $controllerChallenge,
                'sequence' => $metadata['sequence'],
                'pairing_id' => $invitation['pairing_id'],
            ]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }

        $this->log->write('pairing_started', [
            'agentId' => $agentId,
            'pairingId' => $invitation['pairing_id'],
            'remoteAddress' => $remoteAddress,
        ]);
        return $this->signedResponse($metadata, 200, [
            'schemaVersion' => self::SCHEMA_VERSION,
            'pairingId' => $invitation['pairing_id'],
            'agentId' => $agentId,
            'controllerId' => $this->identity->id(),
            'controllerKeyId' => $this->identity->keyId(),
            'agentChallenge' => $agentChallenge,
            'controllerChallenge' => $controllerChallenge,
            'expiresAt' => $this->formatTimestamp(new DateTimeImmutable((string) $invitation['expires_at'])),
        ]);
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function confirmPairing(array $metadata, array $headers, string $rawBody, string $remoteAddress): ControllerResponse
    {
        $body = $this->decodeJSON($rawBody);
        if (($body['schemaVersion'] ?? null) !== self::SCHEMA_VERSION) {
            throw new ControllerError(400, 'invalid_pairing_schema');
        }
        $pairingId = $this->requireString($body, 'pairingId');
        $agentId = $this->requireString($body, 'agentId');
        $agentChallenge = $this->requireString($body, 'agentChallenge');
        $controllerChallenge = $this->requireString($body, 'controllerChallenge');

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "SELECT * FROM edge_agent_pairing_invitations WHERE pairing_id = :pairing_id FOR UPDATE"
            );
            $statement->execute(['pairing_id' => $pairingId]);
            $invitation = $statement->fetch();
            if (!$invitation || $invitation['status'] !== 'pending') {
                throw new ControllerError(403, 'pairing_session_invalid');
            }
            if ((new DateTimeImmutable((string) $invitation['expires_at'])) <= $this->now()) {
                throw new ControllerError(403, 'pairing_session_expired');
            }
            if (!hash_equals((string) $invitation['pending_agent_id'], $agentId) ||
                !hash_equals($agentId, $metadata['signerId']) ||
                !hash_equals((string) $invitation['pending_key_id'], $metadata['keyId']) ||
                !hash_equals((string) $invitation['agent_challenge'], $agentChallenge) ||
                !hash_equals((string) $invitation['controller_challenge'], $controllerChallenge)) {
                throw new ControllerError(401, 'pairing_confirmation_mismatch');
            }
            if ($metadata['sequence'] <= (int) $invitation['pending_last_sequence']) {
                throw new ControllerError(409, 'request_sequence_replayed');
            }
            $this->verifyRequest($metadata, $headers, $rawBody, (string) $invitation['pending_public_key']);
            $this->reserveReplay($agentId, $metadata['requestId']);

            $existing = $this->db->prepare("SELECT public_key FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE");
            $existing->execute(['agent_id' => $agentId]);
            $existingAgent = $existing->fetch();
            if ($existingAgent && !hash_equals((string) $existingAgent['public_key'], (string) $invitation['pending_public_key'])) {
                throw new ControllerError(409, 'agent_identity_already_exists');
            }
            $upsert = $this->db->prepare(<<<'SQL'
                INSERT INTO edge_agents (
                    agent_id, display_name, public_key, key_id, pairing_status, paired_at,
                    last_sequence, agent_version, capabilities, updated_at
                ) VALUES (
                    :agent_id, :display_name, :public_key, :key_id, 'active', now(),
                    :last_sequence, :agent_version, CAST(:capabilities AS jsonb), now()
                )
                ON CONFLICT (agent_id) DO UPDATE SET
                    display_name = EXCLUDED.display_name,
                    key_id = EXCLUDED.key_id,
                    pairing_status = 'active',
                    paired_at = now(),
                    revocation_requested_at = NULL,
                    revocation_reason = NULL,
                    revoked_at = NULL,
                    last_sequence = EXCLUDED.last_sequence,
                    agent_version = EXCLUDED.agent_version,
                    capabilities = EXCLUDED.capabilities,
                    updated_at = now()
                SQL);
            $upsert->execute([
                'agent_id' => $agentId,
                'display_name' => $invitation['pending_agent_name'],
                'public_key' => $invitation['pending_public_key'],
                'key_id' => $invitation['pending_key_id'],
                'last_sequence' => $metadata['sequence'],
                'agent_version' => $invitation['pending_agent_version'],
                'capabilities' => $invitation['pending_capabilities'] ?: '[]',
            ]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_agent_pairing_invitations SET
                    status = 'used', used_at = now(), pending_last_sequence = :sequence
                WHERE pairing_id = :pairing_id
                SQL)->execute(['sequence' => $metadata['sequence'], 'pairing_id' => $pairingId]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }

        $pairedAt = $this->now();
        $this->log->write('pairing_completed', [
            'agentId' => $agentId,
            'pairingId' => $pairingId,
            'remoteAddress' => $remoteAddress,
        ]);
        return $this->signedResponse($metadata, 200, [
            'schemaVersion' => self::SCHEMA_VERSION,
            'paired' => true,
            'agentId' => $agentId,
            'controllerId' => $this->identity->id(),
            'pairedAt' => $this->formatTimestamp($pairedAt),
        ]);
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function sync(array $metadata, array $headers, string $rawBody, string $remoteAddress): ControllerResponse
    {
        $body = $this->decodeJSON($rawBody);
        if (($body['schemaVersion'] ?? null) !== self::SCHEMA_VERSION) {
            throw new ControllerError(400, 'invalid_sync_schema');
        }
        $agentId = $this->requireString($body, 'agentId');
        if (!hash_equals($agentId, $metadata['signerId'])) {
            throw new ControllerError(401, 'agent_identity_mismatch');
        }
        $observedGeneration = $this->requireNonNegativeInteger($body, 'observedGeneration');
        $appliedGeneration = $this->requireNonNegativeInteger($body, 'appliedGeneration');
        if ($appliedGeneration > $observedGeneration) {
            throw new ControllerError(400, 'invalid_generation_order');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare("SELECT * FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE");
            $statement->execute(['agent_id' => $agentId]);
            $agent = $statement->fetch();
            if (!$agent || !in_array($agent['pairing_status'], ['active', 'revoking'], true)) {
                throw new ControllerError(403, 'agent_not_paired');
            }
            if (!hash_equals((string) $agent['key_id'], $metadata['keyId'])) {
                throw new ControllerError(401, 'agent_key_mismatch');
            }
            if ($metadata['sequence'] <= (int) $agent['last_sequence']) {
                throw new ControllerError(409, 'request_sequence_replayed');
            }
            $this->verifyRequest($metadata, $headers, $rawBody, (string) $agent['public_key']);
            $identity = $this->requireArray($body, 'identity');
            if (!hash_equals((string) $agent['public_key'], $this->requireString($identity, 'publicKey')) ||
                !hash_equals((string) $agent['key_id'], $this->requireString($identity, 'keyId'))) {
                throw new ControllerError(401, 'agent_identity_mismatch');
            }
            if ($observedGeneration > (int) $agent['desired_generation']) {
                throw new ControllerError(409, 'observed_generation_ahead_of_controller');
            }
            $this->reserveReplay($agentId, $metadata['requestId']);
            $management = is_array($body['management'] ?? null) ? $body['management'] : [];
            $actualState = is_array($body['actualState'] ?? null) ? $body['actualState'] : [];
            $health = is_array($body['health'] ?? null) ? $body['health'] : [];
            $capabilities = is_array($body['capabilities'] ?? null) ? $body['capabilities'] : [];
            $overlayPublicKey = $this->optionalWireGuardPublicKey($capabilities['overlayPublicKey'] ?? null);
            $conditions = is_array($actualState['conditions'] ?? null) ? $actualState['conditions'] : [];
            if ($agent['pairing_status'] === 'revoking') {
                $update = $this->db->prepare(<<<'SQL'
                    UPDATE edge_agents SET
                        display_name = :display_name,
                        last_seen_at = now(),
                        last_sequence = :sequence,
                        agent_version = :agent_version,
                        capabilities = CAST(:capabilities AS jsonb),
                        overlay_public_key = :overlay_public_key,
                        observed_generation = :observed_generation,
                        applied_generation = :applied_generation,
                        actual_state = CAST(:actual_state AS jsonb),
                        health = CAST(:health AS jsonb),
                        condition_summary = CAST(:conditions AS jsonb),
                        updated_at = now()
                    WHERE agent_id = :agent_id
                    SQL);
                $update->execute([
                    'display_name' => is_string($body['agentName'] ?? null) ? $body['agentName'] : $agent['display_name'],
                    'sequence' => $metadata['sequence'],
                    'agent_version' => is_string($body['agentVersion'] ?? null) ? $body['agentVersion'] : '',
                    'capabilities' => $this->encodeJSON($capabilities),
                    'overlay_public_key' => $overlayPublicKey,
                    'observed_generation' => $observedGeneration,
                    'applied_generation' => $appliedGeneration,
                    'actual_state' => $this->encodeJSON($actualState),
                    'health' => $this->encodeJSON($health),
                    'conditions' => $this->encodeJSON($conditions),
                    'agent_id' => $agentId,
                ]);
                $this->cleanupReplayRows();
                $this->db->commit();
                $this->log->write('agent_revocation_delivered', [
                    'agentId' => $agentId,
                    'remoteAddress' => $remoteAddress,
                ]);
                return $this->signedResponse($metadata, 200, [
                    'schemaVersion' => self::SCHEMA_VERSION,
                    'controllerId' => $this->identity->id(),
                    'serverTime' => $this->formatTimestamp($this->now()),
                    'desiredGeneration' => 0,
                    'desiredState' => (object) ['overlay' => (object) [], 'mappings' => []],
                    'actions' => [],
                    'revoke' => true,
                    'revokeReason' => is_string($agent['revocation_reason']) && $agent['revocation_reason'] !== ''
                        ? $agent['revocation_reason']
                        : 'revoked_by_rbt_admin',
                ]);
            }
            $managementEnabled = ($management['enabled'] ?? false) === true;
            $managementAuthorizedByAgent = ($management['authorized'] ?? false) === true;
            $managementRevision = $management['credentialRevision'] ?? 0;
            if (!is_int($managementRevision) || $managementRevision < 0) {
                throw new ControllerError(400, 'management_revision_invalid');
            }
            try {
                $managementScopes = ManagedAuthorization::normalizeScopes(
                    is_array($management['scopes'] ?? null) ? $management['scopes'] : [],
                );
                $storedScopes = ManagedAuthorization::normalizeScopes(
                    is_string($agent['managed_scopes'])
                        ? (json_decode($agent['managed_scopes'], true, flags: JSON_THROW_ON_ERROR) ?: [])
                        : [],
                );
                $requestedScopes = ManagedAuthorization::normalizeScopes(
                    is_string($agent['management_requested_scopes'])
                        ? (json_decode($agent['management_requested_scopes'], true, flags: JSON_THROW_ON_ERROR) ?: [])
                        : [],
                );
            } catch (Throwable) {
                throw new ControllerError(400, 'management_scopes_invalid');
            }
            $pendingExpiresAt = $agent['management_expires_at']
                ? new DateTimeImmutable((string) $agent['management_expires_at'])
                : null;
            $pendingValid = $agent['management_challenge'] &&
                $agent['management_proof'] &&
                $pendingExpiresAt !== null &&
                $pendingExpiresAt > $this->now() &&
                (int) $agent['management_revision'] === $managementRevision &&
                $requestedScopes === $managementScopes;
            $managedAuthorized = false;
            $managementResponse = null;
            $clearManagementChallenge = !$pendingValid;

            if (!$managementEnabled) {
                $managedAuthorized = false;
                $clearManagementChallenge = true;
            } elseif ($managementAuthorizedByAgent) {
                $alreadyAuthorized = (bool) $agent['managed_authorized'] &&
                    (int) $agent['management_revision'] === $managementRevision &&
                    $storedScopes === $managementScopes;
                if (!$alreadyAuthorized && !$pendingValid) {
                    throw new ControllerError(409, 'managed_authorization_not_proven');
                }
                $managedAuthorized = true;
                $clearManagementChallenge = true;
            } elseif ($pendingValid) {
                $managementResponse = [
                    'revision' => $managementRevision,
                    'challenge' => $agent['management_challenge'],
                    'scopes' => $managementScopes,
                    'proof' => $agent['management_proof'],
                ];
            }

            $storedManagementRevision = $managedAuthorized || $pendingValid ? $managementRevision : 0;
            $storedManagedScopes = $managedAuthorized ? $managementScopes : [];
            $challengeValue = $clearManagementChallenge ? null : $agent['management_challenge'];
            $proofValue = $clearManagementChallenge ? null : $agent['management_proof'];
            $requestedScopesValue = $clearManagementChallenge ? [] : $requestedScopes;
            $expiresValue = $clearManagementChallenge ? null : $agent['management_expires_at'];
            $update = $this->db->prepare(<<<'SQL'
                UPDATE edge_agents SET
                    display_name = :display_name,
                    last_seen_at = now(),
                    last_sequence = :sequence,
                    agent_version = :agent_version,
                    capabilities = CAST(:capabilities AS jsonb),
                    overlay_public_key = :overlay_public_key,
                    management_state = CAST(:management_state AS jsonb),
                    managed_authorized = :managed_authorized,
                    managed_scopes = CAST(:managed_scopes AS jsonb),
                    management_revision = :management_revision,
                    management_challenge = :management_challenge,
                    management_proof = :management_proof,
                    management_requested_scopes = CAST(:management_requested_scopes AS jsonb),
                    management_expires_at = :management_expires_at,
                    observed_generation = :observed_generation,
                    applied_generation = :applied_generation,
                    actual_state = CAST(:actual_state AS jsonb),
                    health = CAST(:health AS jsonb),
                    condition_summary = CAST(:conditions AS jsonb),
                    updated_at = now()
                WHERE agent_id = :agent_id
                SQL);
            $update->execute([
                'display_name' => is_string($body['agentName'] ?? null) ? $body['agentName'] : $agent['display_name'],
                'sequence' => $metadata['sequence'],
                'agent_version' => is_string($body['agentVersion'] ?? null) ? $body['agentVersion'] : '',
                'capabilities' => $this->encodeJSON($capabilities),
                'overlay_public_key' => $overlayPublicKey,
                'management_state' => $this->encodeJSON($management),
                'managed_authorized' => $managedAuthorized ? 'true' : 'false',
                'managed_scopes' => $this->encodeJSON($storedManagedScopes),
                'management_revision' => $storedManagementRevision,
                'management_challenge' => $challengeValue,
                'management_proof' => $proofValue,
                'management_requested_scopes' => $this->encodeJSON($requestedScopesValue),
                'management_expires_at' => $expiresValue,
                'observed_generation' => $observedGeneration,
                'applied_generation' => $appliedGeneration,
                'actual_state' => $this->encodeJSON($actualState),
                'health' => $this->encodeJSON($health),
                'conditions' => $this->encodeJSON($conditions),
                'agent_id' => $agentId,
            ]);
            if ($overlayPublicKey !== null) {
                $this->db->prepare(<<<'SQL'
                    UPDATE edge_overlay_leases SET
                        agent_public_key = :public_key,
                        updated_at = now()
                    WHERE agent_id = :agent_id
                    SQL)->execute([
                        'public_key' => $overlayPublicKey,
                        'agent_id' => $agentId,
                    ]);
            }
            $desiredEnabled = $managedAuthorized;
            $desiredGeneration = $desiredEnabled ? (int) $agent['desired_generation'] : 0;
            $desiredState = $desiredEnabled
                ? json_decode((string) $agent['desired_state'], false, flags: JSON_THROW_ON_ERROR)
                : (object) ['overlay' => (object) [], 'mappings' => []];
            $actions = $desiredEnabled ? $this->loadActions($agentId) : [];
            $this->cleanupReplayRows();
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }

        $response = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'controllerId' => $this->identity->id(),
            'serverTime' => $this->formatTimestamp($this->now()),
            'desiredGeneration' => $desiredGeneration,
            'desiredState' => $desiredState,
            'actions' => $actions,
        ];
        if ($managementResponse !== null) {
            $response['management'] = $managementResponse;
        }
        return $this->signedResponse($metadata, 200, $response);
    }

    /** @return list<array<string,mixed>> */
    private function loadActions(string $agentId): array
    {
        $statement = $this->db->prepare(<<<'SQL'
            SELECT action_id, action_type, payload, expires_at, idempotency_key
            FROM edge_agent_actions
            WHERE agent_id = :agent_id AND state IN ('queued', 'running') AND expires_at > now()
            ORDER BY created_at
            LIMIT 20
            SQL);
        $statement->execute(['agent_id' => $agentId]);
        $actions = [];
        foreach ($statement->fetchAll() as $row) {
            $actions[] = [
                'actionId' => $row['action_id'],
                'type' => $row['action_type'],
                'payload' => json_decode((string) $row['payload'], false, flags: JSON_THROW_ON_ERROR),
                'expiresAt' => $this->formatTimestamp(new DateTimeImmutable((string) $row['expires_at'])),
                'idempotencyKey' => $row['idempotency_key'],
            ];
        }
        if ($actions !== []) {
            $ids = array_column($actions, 'actionId');
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $update = $this->db->prepare(
                "UPDATE edge_agent_actions SET state = 'running', started_at = COALESCE(started_at, now()), updated_at = now() " .
                "WHERE action_id IN ($placeholders) AND state = 'queued'"
            );
            $update->execute($ids);
        }
        $this->db->exec(<<<'SQL'
            UPDATE edge_agent_actions SET state = 'expired', error = 'action_expired',
                completed_at = COALESCE(completed_at, now()), updated_at = now()
            WHERE state IN ('queued', 'running') AND expires_at <= now()
            SQL);
        $this->db->exec(<<<'SQL'
            DELETE FROM edge_agent_actions
            WHERE state IN ('completed', 'failed', 'expired')
              AND updated_at < now() - interval '7 days'
            SQL);
        return $actions;
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function actionResult(
        array $metadata,
        array $headers,
        string $rawBody,
        string $remoteAddress,
        string $pathActionId,
    ): ControllerResponse {
        $body = $this->decodeJSON($rawBody);
        if (($body['schemaVersion'] ?? null) !== self::SCHEMA_VERSION) {
            throw new ControllerError(400, 'invalid_action_result_schema');
        }
        $agentId = $this->requireString($body, 'agentId');
        $actionId = $this->requireString($body, 'actionId');
        $idempotencyKey = $this->requireString($body, 'idempotencyKey');
        $state = $this->requireString($body, 'state');
        if (!hash_equals($pathActionId, $actionId) || !in_array($state, ['completed', 'failed'], true)) {
            throw new ControllerError(400, 'invalid_action_result');
        }
        $result = is_array($body['result'] ?? null) ? $body['result'] : [];
        $error = is_string($body['error'] ?? null) ? trim($body['error']) : '';
        if (strlen($error) > 2000) {
            throw new ControllerError(400, 'action_error_too_long');
        }

        $this->db->beginTransaction();
        try {
            $this->lockAndVerifyAgent($metadata, $headers, $rawBody, $agentId);
            $statement = $this->db->prepare(
                'SELECT * FROM edge_agent_actions WHERE action_id = :action_id AND agent_id = :agent_id FOR UPDATE'
            );
            $statement->execute(['action_id' => $actionId, 'agent_id' => $agentId]);
            $action = $statement->fetch();
            if (!$action || !hash_equals((string) $action['idempotency_key'], $idempotencyKey)) {
                throw new ControllerError(404, 'action_not_found');
            }
            if (!in_array($action['state'], ['completed', 'failed'], true) &&
                new DateTimeImmutable((string) $action['expires_at']) <= $this->now()) {
                throw new ControllerError(410, 'action_expired');
            }
            if (in_array($action['state'], ['completed', 'failed'], true)) {
                if (!hash_equals((string) $action['state'], $state)) {
                    throw new ControllerError(409, 'action_result_conflict');
                }
                $storedResult = json_decode((string) ($action['result'] ?: '{}'), true, flags: JSON_THROW_ON_ERROR);
                $storedError = is_string($action['error']) ? $action['error'] : '';
                if ($storedResult != $result || !hash_equals($storedError, $error)) {
                    throw new ControllerError(409, 'action_result_conflict');
                }
            } else {
                $update = $this->db->prepare(<<<'SQL'
                    UPDATE edge_agent_actions SET state = :state, result = CAST(:result AS jsonb),
                        error = :error, completed_at = now(), updated_at = now()
                    WHERE action_id = :action_id
                    SQL);
                $update->execute([
                    'state' => $state,
                    'result' => $this->encodeJSON((object) $result),
                    'error' => $error !== '' ? $error : null,
                    'action_id' => $actionId,
                ]);
            }
            $this->db->prepare(
                'UPDATE edge_agents SET last_sequence = :sequence, updated_at = now() WHERE agent_id = :agent_id'
            )->execute(['sequence' => $metadata['sequence'], 'agent_id' => $agentId]);
            $this->db->commit();
        } catch (Throwable $errorValue) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $errorValue;
        }
        $this->log->write('agent_action_result', [
            'agentId' => $agentId,
            'actionId' => $actionId,
            'state' => $state,
            'remoteAddress' => $remoteAddress,
        ]);
        return $this->signedResponse($metadata, 200, [
            'schemaVersion' => self::SCHEMA_VERSION,
            'actionId' => $actionId,
            'accepted' => true,
        ]);
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function revoke(array $metadata, array $headers, string $rawBody, string $remoteAddress): ControllerResponse
    {
        $body = $this->decodeJSON($rawBody);
        if (($body['schemaVersion'] ?? null) !== self::SCHEMA_VERSION) {
            throw new ControllerError(400, 'invalid_revoke_schema');
        }
        $agentId = $this->requireString($body, 'agentId');
        $this->db->beginTransaction();
        try {
            $this->lockAndVerifyAgent($metadata, $headers, $rawBody, $agentId, ['active', 'revoking']);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_agents SET pairing_status = 'revoked', revoked_at = now(),
                    revocation_requested_at = COALESCE(revocation_requested_at, now()),
                    last_sequence = :sequence, managed_authorized = false,
                    managed_scopes = '[]'::jsonb, desired_generation = desired_generation + 1,
                    desired_state = '{"overlay":{},"mappings":[]}'::jsonb,
                    updated_at = now()
                WHERE agent_id = :agent_id
                SQL)->execute(['sequence' => $metadata['sequence'], 'agent_id' => $agentId]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_overlay_leases SET enabled = false, gateway_state = 'pending',
                    gateway_error = NULL, updated_at = now()
                WHERE agent_id = :agent_id
                SQL)->execute(['agent_id' => $agentId]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_agent_actions SET state = 'expired', error = 'agent_revoked_pairing',
                    completed_at = now(), updated_at = now()
                WHERE agent_id = :agent_id AND state IN ('queued', 'running')
                SQL)->execute(['agent_id' => $agentId]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
        $this->log->write('agent_pairing_revoked_by_agent', [
            'agentId' => $agentId,
            'remoteAddress' => $remoteAddress,
        ]);
        return $this->signedResponse($metadata, 200, [
            'schemaVersion' => self::SCHEMA_VERSION,
            'agentId' => $agentId,
            'revoked' => true,
        ]);
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers @return array<string,mixed> */
    private function lockAndVerifyAgent(
        array $metadata,
        array $headers,
        string $rawBody,
        string $agentId,
        array $allowedStatuses = ['active'],
    ): array
    {
        if (!hash_equals($agentId, (string) $metadata['signerId'])) {
            throw new ControllerError(401, 'agent_identity_mismatch');
        }
        $statement = $this->db->prepare('SELECT * FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE');
        $statement->execute(['agent_id' => $agentId]);
        $agent = $statement->fetch();
        if (!$agent || !in_array($agent['pairing_status'], $allowedStatuses, true)) {
            throw new ControllerError(403, 'agent_not_paired');
        }
        if (!hash_equals((string) $agent['key_id'], (string) $metadata['keyId'])) {
            throw new ControllerError(401, 'agent_key_mismatch');
        }
        if ((int) $metadata['sequence'] <= (int) $agent['last_sequence']) {
            throw new ControllerError(409, 'request_sequence_replayed');
        }
        $this->verifyRequest($metadata, $headers, $rawBody, (string) $agent['public_key']);
        $this->reserveReplay($agentId, (string) $metadata['requestId']);
        return $agent;
    }

    /** @param array<string,mixed> $metadata @param array<string,string> $headers */
    private function verifyRequest(array $metadata, array $headers, string $body, string $publicKey): void
    {
        try {
            Protocol::validateTimestamp($metadata, $this->now(), self::MAX_CLOCK_SKEW_SECONDS);
            Protocol::verify($publicKey, $metadata, $this->header($headers, Protocol::HEADER_SIGNATURE), $body);
        } catch (Throwable $error) {
            throw new ControllerError(401, $error->getMessage());
        }
    }

    private function reserveReplay(string $signerId, string $requestId): void
    {
        try {
            $statement = $this->db->prepare(<<<'SQL'
                INSERT INTO edge_agent_request_replay (signer_id, request_id, expires_at)
                VALUES (:signer_id, :request_id, now() + CAST(:ttl AS integer) * interval '1 second')
                SQL);
            $statement->execute([
                'signer_id' => $signerId,
                'request_id' => $requestId,
                'ttl' => self::REPLAY_TTL_SECONDS,
            ]);
        } catch (Throwable) {
            throw new ControllerError(409, 'request_id_replayed');
        }
    }

    private function cleanupReplayRows(): void
    {
        $this->db->exec("DELETE FROM edge_agent_request_replay WHERE expires_at < now()");
    }

    /** @param array<string,mixed> $requestMetadata @param array<string,mixed>|object $payload */
    private function signedResponse(array $requestMetadata, int $status, array|object $payload): ControllerResponse
    {
        $body = $this->encodeJSON($payload);
        $metadata = [
            'direction' => 'response',
            'method' => $requestMetadata['method'],
            'path' => $requestMetadata['path'],
            'statusCode' => $status,
            'bodySha256' => Protocol::bodySha256($body),
            'signerId' => $this->identity->id(),
            'keyId' => $this->identity->keyId(),
            'timestamp' => $this->formatTimestamp($this->now()),
            'requestId' => $requestMetadata['requestId'],
            'sequence' => $requestMetadata['sequence'],
        ];
        $signature = Protocol::sign($this->identity->privateKey(), $metadata);
        return new ControllerResponse($status, $body, $this->responseHeaders($metadata, $signature));
    }

    /** @param array<string,mixed>|null $requestMetadata */
    private function errorResponse(string $method, string $path, ?array $requestMetadata, int $status, string $error): ControllerResponse
    {
        $payload = ['error' => $error];
        if ($requestMetadata !== null) {
            return $this->signedResponse($requestMetadata, $status, $payload);
        }
        return new ControllerResponse(
            $status,
            $this->encodeJSON($payload),
            ['Content-Type' => 'application/json; charset=utf-8', 'Cache-Control' => 'no-store'],
        );
    }

    /** @param array<string,mixed> $metadata @return array<string,string> */
    private function responseHeaders(array $metadata, string $signature): array
    {
        return [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
            Protocol::HEADER_SIGNATURE_VERSION => Protocol::VERSION,
            Protocol::HEADER_SIGNER_ID => (string) $metadata['signerId'],
            Protocol::HEADER_KEY_ID => (string) $metadata['keyId'],
            Protocol::HEADER_TIMESTAMP => (string) $metadata['timestamp'],
            Protocol::HEADER_REQUEST_ID => (string) $metadata['requestId'],
            Protocol::HEADER_SEQUENCE => (string) $metadata['sequence'],
            Protocol::HEADER_CONTENT_SHA256 => (string) $metadata['bodySha256'],
            Protocol::HEADER_SIGNATURE => $signature,
        ];
    }

    /** @param array<string,string> $headers @return array<string,mixed> */
    private function metadataFromHeaders(array $headers, string $direction, string $method, string $path, int $statusCode): array
    {
        if (!hash_equals(Protocol::VERSION, $this->header($headers, Protocol::HEADER_SIGNATURE_VERSION))) {
            throw new ControllerError(401, 'signature_version_invalid');
        }
        $sequence = filter_var($this->header($headers, Protocol::HEADER_SEQUENCE), FILTER_VALIDATE_INT);
        if (!is_int($sequence) || $sequence <= 0) {
            throw new ControllerError(401, 'signature_sequence_invalid');
        }
        $metadata = [
            'direction' => $direction,
            'method' => strtoupper($method),
            'path' => $path,
            'statusCode' => $statusCode,
            'bodySha256' => $this->header($headers, Protocol::HEADER_CONTENT_SHA256),
            'signerId' => $this->header($headers, Protocol::HEADER_SIGNER_ID),
            'keyId' => $this->header($headers, Protocol::HEADER_KEY_ID),
            'timestamp' => $this->header($headers, Protocol::HEADER_TIMESTAMP),
            'requestId' => $this->header($headers, Protocol::HEADER_REQUEST_ID),
            'sequence' => $sequence,
        ];
        try {
            Protocol::canonical($metadata);
        } catch (Throwable $error) {
            throw new ControllerError(401, $error->getMessage());
        }
        return $metadata;
    }

    /** @param array<string,string> $headers */
    private function header(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return trim((string) $value);
            }
        }
        return '';
    }

    /** @return array<string,mixed> */
    private function decodeJSON(string $body): array
    {
        try {
            $decoded = json_decode($body, true, 128, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new ControllerError(400, 'invalid_json');
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new ControllerError(400, 'json_object_required');
        }
        return $decoded;
    }

    /** @param array<string,mixed>|object $value */
    private function encodeJSON(array|object $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /** @param array<string,mixed> $source @return array<string,mixed> */
    private function requireArray(array $source, string $key): array
    {
        if (!isset($source[$key]) || !is_array($source[$key]) || array_is_list($source[$key])) {
            throw new ControllerError(400, "$key must be an object");
        }
        return $source[$key];
    }

    /** @param array<string,mixed> $source */
    private function requireString(array $source, string $key): string
    {
        if (!isset($source[$key]) || !is_string($source[$key]) || trim($source[$key]) === '') {
            throw new ControllerError(400, "$key is required");
        }
        return trim($source[$key]);
    }

    /** @param array<string,mixed> $source */
    private function requireNonNegativeInteger(array $source, string $key): int
    {
        $value = $source[$key] ?? null;
        if (!is_int($value) || $value < 0) {
            throw new ControllerError(400, "$key must be a non-negative integer");
        }
        return $value;
    }

    private function optionalWireGuardPublicKey(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_string($value)) {
            throw new ControllerError(400, 'overlay_public_key_invalid');
        }
        $decoded = base64_decode(trim($value), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new ControllerError(400, 'overlay_public_key_invalid');
        }
        return trim($value);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private function formatTimestamp(DateTimeImmutable $value): string
    {
        return $value->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}

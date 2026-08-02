<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PDO;
use Throwable;

final class DesiredStateService
{
    public function __construct(
        private readonly PDO $db,
        private readonly ControllerIdentity $identity,
        private readonly JsonLog $log,
    ) {
    }

    /** @return array<string,mixed> */
    public function summary(): array
    {
        $agents = $this->db->query(<<<'SQL'
            SELECT a.agent_id, a.display_name, a.pairing_status, a.last_seen_at,
                   a.agent_version, a.managed_authorized, a.managed_scopes,
                   a.desired_generation, a.observed_generation, a.applied_generation,
                   a.condition_summary, a.overlay_public_key,
                   l.lease_id, l.overlay_prefix, l.tunnel_ip, l.enabled AS lease_enabled,
                   l.gateway_state, l.gateway_error, p.title AS pool_title,
                   (SELECT count(*) FROM edge_overlay_mappings m
                    WHERE m.agent_id = a.agent_id AND m.enabled) AS mapping_count
            FROM edge_agents a
            LEFT JOIN edge_overlay_leases l ON l.agent_id = a.agent_id
            LEFT JOIN edge_overlay_pools p ON p.pool_id = l.pool_id
            ORDER BY a.display_name, a.agent_id
            SQL)->fetchAll();
        foreach ($agents as &$agent) {
            $agent = $this->normalizeAgentRow($agent);
        }
        unset($agent);
        return [
            'controller' => $this->identity->publicData(),
            'agents' => $agents,
            'pools' => $this->listPools(),
        ];
    }

    /** @return array<string,mixed> */
    public function agent(string $agentId): array
    {
        $statement = $this->db->prepare(<<<'SQL'
            SELECT a.*, l.lease_id, l.pool_id, host(l.tunnel_ip) AS tunnel_ip,
                   l.overlay_prefix::text AS overlay_prefix,
                   l.agent_public_key, l.enabled AS lease_enabled, l.gateway_state,
                   l.gateway_error, l.gateway_applied_at,
                   p.title AS pool_title, p.overlay_type, p.gateway_endpoint,
                   p.gateway_public_key, p.gateway_tunnel_address,
                   p.gateway_interface, p.allowed_source_prefixes, p.parameters,
                   p.persistent_keepalive_sec
            FROM edge_agents a
            LEFT JOIN edge_overlay_leases l ON l.agent_id = a.agent_id
            LEFT JOIN edge_overlay_pools p ON p.pool_id = l.pool_id
            WHERE a.agent_id = :agent_id
            SQL);
        $statement->execute(['agent_id' => $agentId]);
        $agent = $statement->fetch();
        if (!$agent) {
            throw new InvalidArgumentException('agent_not_found');
        }
        $mappings = $this->db->prepare(<<<'SQL'
            SELECT mapping_id, host(local_ip) AS local_ip, host(overlay_ip) AS overlay_ip, enabled, comment,
                   desired_generation, current_state, current_error, created_at, updated_at
            FROM edge_overlay_mappings
            WHERE agent_id = :agent_id
            ORDER BY overlay_ip, mapping_id
            SQL);
        $mappings->execute(['agent_id' => $agentId]);
        $actions = $this->db->prepare(<<<'SQL'
            SELECT action_id, action_type, state, result, error, expires_at, created_at, completed_at
            FROM edge_agent_actions
            WHERE agent_id = :agent_id
            ORDER BY created_at DESC
            LIMIT 50
            SQL);
        $actions->execute(['agent_id' => $agentId]);
        $normalized = $this->normalizeAgentRow($agent);
        $normalized['mappings'] = array_map(fn(array $row): array => $this->normalizeRow($row), $mappings->fetchAll());
        $normalized['actions'] = array_map(fn(array $row): array => $this->normalizeRow($row), $actions->fetchAll());
        return $normalized;
    }

    /** @return list<array<string,mixed>> */
    public function listPools(): array
    {
        $rows = $this->db->query(<<<'SQL'
            SELECT p.*,
                   (SELECT count(*) FROM edge_overlay_leases l WHERE l.pool_id = p.pool_id) AS lease_count
            FROM edge_overlay_pools p
            ORDER BY p.title, p.pool_id
            SQL)->fetchAll();
        return array_map(fn(array $row): array => $this->normalizeRow($row), $rows);
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function savePool(array $input): array
    {
        $poolId = $this->identifier($input['poolId'] ?? null, 'pool');
        $title = $this->requiredString($input, 'title');
        $tunnelPool = IPv4::parsePrefix($this->requiredString($input, 'tunnelPool'), 8, 30)['canonical'];
        $overlayPool = IPv4::parsePrefix($this->requiredString($input, 'overlayPool'), 8, 30)['canonical'];
        if (IPv4::overlaps($tunnelPool, $overlayPool)) {
            throw new InvalidArgumentException('overlay_and_tunnel_pools_overlap');
        }
        $agentPrefixLength = $this->integer($input['agentPrefixLength'] ?? 24, 8, 30, 'agentPrefixLength');
        if ($agentPrefixLength < IPv4::parsePrefix($overlayPool)['bits']) {
            throw new InvalidArgumentException('agent_prefix_outside_overlay_pool');
        }
        $gatewayEndpoint = $this->requiredString($input, 'gatewayEndpoint');
        if (!$this->validEndpoint($gatewayEndpoint)) {
            throw new InvalidArgumentException('gateway_endpoint_invalid');
        }
        $gatewayPublicKey = $this->wireGuardPublicKey($this->requiredString($input, 'gatewayPublicKey'));
        $gatewayTunnelAddress = IPv4::normalizeAddress($this->requiredString($input, 'gatewayTunnelAddress'));
        if (!IPv4::contains($tunnelPool, $gatewayTunnelAddress, true)) {
            throw new InvalidArgumentException('gateway_tunnel_address_outside_pool');
        }
        $gatewayInterface = $this->interfaceName($input['gatewayInterface'] ?? 'awg-rbt');
        $overlayType = trim((string) ($input['overlayType'] ?? 'wireguard'));
        if (!in_array($overlayType, ['amneziawg', 'wireguard'], true)) {
            throw new InvalidArgumentException('overlay_type_invalid');
        }
        $keepalive = $this->integer($input['persistentKeepaliveSec'] ?? 25, 0, 3600, 'persistentKeepaliveSec');
        $allowedSources = $this->prefixList($input['allowedSourcePrefixes'] ?? []);
        $allowedSources[] = $gatewayTunnelAddress . '/32';
        $allowedSources = $this->prefixList($allowedSources);
        if ($allowedSources === []) {
            throw new InvalidArgumentException('allowed_source_prefix_required');
        }
        $parameters = $this->awgParameters($input['parameters'] ?? []);
        if ($overlayType === 'wireguard' && $parameters !== []) {
            throw new InvalidArgumentException('wireguard_pool_cannot_have_awg_parameters');
        }
        $enabled = ($input['enabled'] ?? true) === true;

        return $this->transaction(function () use (
            $poolId,
            $title,
            $tunnelPool,
            $overlayPool,
            $agentPrefixLength,
            $gatewayEndpoint,
            $gatewayPublicKey,
            $gatewayTunnelAddress,
            $gatewayInterface,
            $overlayType,
            $keepalive,
            $allowedSources,
            $parameters,
            $enabled,
        ): array {
            $this->db->exec("SELECT pg_advisory_xact_lock(hashtext('rbt_edge_overlay_pool_allocation'))");
            $ranges = $this->db->prepare(<<<'SQL'
                SELECT pool_id, tunnel_pool::text AS tunnel_pool, overlay_pool::text AS overlay_pool
                FROM edge_overlay_pools
                WHERE pool_id <> :pool_id
                FOR UPDATE
                SQL);
            $ranges->execute(['pool_id' => $poolId]);
            foreach ($ranges->fetchAll() as $existingPool) {
                foreach ([$tunnelPool, $overlayPool] as $candidateRange) {
                    foreach ([(string) $existingPool['tunnel_pool'], (string) $existingPool['overlay_pool']] as $existingRange) {
                        if (IPv4::overlaps($candidateRange, $existingRange)) {
                            throw new InvalidArgumentException('network_pool_overlaps_existing_pool');
                        }
                    }
                }
            }
            $leases = $this->db->prepare(<<<'SQL'
                SELECT host(tunnel_ip) AS tunnel_ip, overlay_prefix::text AS overlay_prefix
                FROM edge_overlay_leases
                WHERE pool_id = :pool_id
                FOR UPDATE
                SQL);
            $leases->execute(['pool_id' => $poolId]);
            foreach ($leases->fetchAll() as $lease) {
                if (!IPv4::contains($tunnelPool, (string) $lease['tunnel_ip'], true)) {
                    throw new InvalidArgumentException('existing_tunnel_lease_outside_new_pool');
                }
                $leasePrefix = IPv4::parsePrefix((string) $lease['overlay_prefix']);
                if ($leasePrefix['bits'] !== $agentPrefixLength ||
                    !IPv4::contains($overlayPool, explode('/', $leasePrefix['canonical'], 2)[0])) {
                    throw new InvalidArgumentException('existing_overlay_lease_outside_new_pool');
                }
            }
            $interfaceConflict = $this->db->prepare(<<<'SQL'
                SELECT pool_id FROM edge_overlay_pools
                WHERE pool_id <> :pool_id
                  AND (gateway_interface = :gateway_interface OR gateway_endpoint = :gateway_endpoint)
                LIMIT 1
                FOR UPDATE
                SQL);
            $interfaceConflict->execute([
                'pool_id' => $poolId,
                'gateway_interface' => $gatewayInterface,
                'gateway_endpoint' => $gatewayEndpoint,
            ]);
            if ($interfaceConflict->fetch()) {
                throw new InvalidArgumentException('gateway_interface_or_endpoint_already_used');
            }
            $statement = $this->db->prepare(<<<'SQL'
            INSERT INTO edge_overlay_pools (
                pool_id, title, tunnel_pool, overlay_pool, agent_prefix_length,
                gateway_endpoint, gateway_public_key, gateway_tunnel_address,
                gateway_interface, overlay_type, persistent_keepalive_sec,
                allowed_source_prefixes, parameters, enabled, updated_at
            ) VALUES (
                :pool_id, :title, CAST(:tunnel_pool AS cidr), CAST(:overlay_pool AS cidr), :agent_prefix_length,
                :gateway_endpoint, :gateway_public_key, CAST(:gateway_tunnel_address AS inet),
                :gateway_interface, :overlay_type, :persistent_keepalive_sec,
                CAST(:allowed_source_prefixes AS jsonb), CAST(:parameters AS jsonb),
                CAST(:enabled AS boolean), now()
            )
            ON CONFLICT (pool_id) DO UPDATE SET
                title = EXCLUDED.title,
                tunnel_pool = EXCLUDED.tunnel_pool,
                overlay_pool = EXCLUDED.overlay_pool,
                agent_prefix_length = EXCLUDED.agent_prefix_length,
                gateway_endpoint = EXCLUDED.gateway_endpoint,
                gateway_public_key = EXCLUDED.gateway_public_key,
                gateway_tunnel_address = EXCLUDED.gateway_tunnel_address,
                gateway_interface = EXCLUDED.gateway_interface,
                overlay_type = EXCLUDED.overlay_type,
                persistent_keepalive_sec = EXCLUDED.persistent_keepalive_sec,
                allowed_source_prefixes = EXCLUDED.allowed_source_prefixes,
                parameters = EXCLUDED.parameters,
                enabled = EXCLUDED.enabled,
                updated_at = now()
            RETURNING *
            SQL);
            $statement->execute([
                'pool_id' => $poolId,
                'title' => $title,
                'tunnel_pool' => $tunnelPool,
                'overlay_pool' => $overlayPool,
                'agent_prefix_length' => $agentPrefixLength,
                'gateway_endpoint' => $gatewayEndpoint,
                'gateway_public_key' => $gatewayPublicKey,
                'gateway_tunnel_address' => $gatewayTunnelAddress,
                'gateway_interface' => $gatewayInterface,
                'overlay_type' => $overlayType,
                'persistent_keepalive_sec' => $keepalive,
                'allowed_source_prefixes' => $this->json($allowedSources),
                'parameters' => $this->json($parameters === [] ? (object) [] : $parameters),
                'enabled' => $enabled ? 'true' : 'false',
            ]);
            $saved = $statement->fetch();
            $this->rebuildPoolAgents($poolId);
            $this->log->write('overlay_pool_saved', ['poolId' => $poolId, 'overlayType' => $overlayType]);
            return $this->normalizeRow($saved);
        });
    }

    /** @return array<string,mixed> */
    public function assignPool(string $agentId, string $poolId, bool $enabled = true): array
    {
        return $this->transaction(function () use ($agentId, $poolId, $enabled): array {
            $agent = $this->lockedAgent($agentId);
            $this->requireManagedScope($agent, 'overlay.configure');
            if (!is_string($agent['overlay_public_key']) || trim($agent['overlay_public_key']) === '') {
                throw new InvalidArgumentException('agent_overlay_public_key_not_reported');
            }
            $pool = $this->lockedPool($poolId);
            if (!$this->boolValue($pool['enabled'])) {
                throw new InvalidArgumentException('overlay_pool_disabled');
            }
            $existing = $this->db->prepare('SELECT * FROM edge_overlay_leases WHERE agent_id = :agent_id FOR UPDATE');
            $existing->execute(['agent_id' => $agentId]);
            $lease = $existing->fetch();
            if (!$lease) {
                $usedTunnel = $this->db->prepare('SELECT host(tunnel_ip) AS value FROM edge_overlay_leases WHERE pool_id = :pool_id');
                $usedTunnel->execute(['pool_id' => $poolId]);
                $tunnelIP = IPv4::firstUsableAddress(
                    (string) $pool['tunnel_pool'],
                    array_column($usedTunnel->fetchAll(), 'value'),
                    [(string) $pool['gateway_tunnel_address']],
                );
                $usedPrefixes = $this->db->prepare('SELECT overlay_prefix::text AS value FROM edge_overlay_leases WHERE pool_id = :pool_id');
                $usedPrefixes->execute(['pool_id' => $poolId]);
                $overlayPrefix = IPv4::firstSubnet(
                    (string) $pool['overlay_pool'],
                    (int) $pool['agent_prefix_length'],
                    array_column($usedPrefixes->fetchAll(), 'value'),
                );
                $leaseId = $this->identifier(null, 'lease');
                $insert = $this->db->prepare(<<<'SQL'
                    INSERT INTO edge_overlay_leases (
                        lease_id, agent_id, pool_id, tunnel_ip, overlay_prefix,
                        agent_public_key, enabled, updated_at
                    ) VALUES (
                        :lease_id, :agent_id, :pool_id, CAST(:tunnel_ip AS inet), CAST(:overlay_prefix AS cidr),
                        :agent_public_key, CAST(:enabled AS boolean), now()
                    )
                    SQL);
                $insert->execute([
                    'lease_id' => $leaseId,
                    'agent_id' => $agentId,
                    'pool_id' => $poolId,
                    'tunnel_ip' => $tunnelIP,
                    'overlay_prefix' => $overlayPrefix,
                    'agent_public_key' => $agent['overlay_public_key'],
                    'enabled' => $enabled ? 'true' : 'false',
                ]);
            } else {
                if (!hash_equals((string) $lease['pool_id'], $poolId)) {
                    $mappingCount = $this->db->prepare('SELECT count(*) FROM edge_overlay_mappings WHERE agent_id = :agent_id');
                    $mappingCount->execute(['agent_id' => $agentId]);
                    if ((int) $mappingCount->fetchColumn() > 0) {
                        throw new InvalidArgumentException('delete_mappings_before_changing_pool');
                    }
                    throw new InvalidArgumentException('pool_reassignment_requires_lease_release');
                }
                $this->db->prepare(<<<'SQL'
                    UPDATE edge_overlay_leases SET enabled = CAST(:enabled AS boolean),
                        agent_public_key = COALESCE(:agent_public_key, agent_public_key), updated_at = now()
                    WHERE agent_id = :agent_id
                    SQL)->execute([
                        'enabled' => $enabled ? 'true' : 'false',
                        'agent_public_key' => $agent['overlay_public_key'],
                        'agent_id' => $agentId,
                    ]);
            }
            $generation = $this->rebuildDesiredStateLocked($agentId);
            $this->log->write('overlay_lease_assigned', [
                'agentId' => $agentId,
                'poolId' => $poolId,
                'enabled' => $enabled,
                'desiredGeneration' => $generation,
            ]);
            return $this->agent($agentId);
        });
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function saveMapping(string $agentId, array $input): array
    {
        return $this->transaction(function () use ($agentId, $input): array {
            $agent = $this->lockedAgent($agentId);
            $this->requireManagedScope($agent, 'mapping.configure');
            $leaseStatement = $this->db->prepare(<<<'SQL'
                SELECT l.*, p.enabled AS pool_enabled
                FROM edge_overlay_leases l
                JOIN edge_overlay_pools p ON p.pool_id = l.pool_id
                WHERE l.agent_id = :agent_id
                FOR UPDATE
                SQL);
            $leaseStatement->execute(['agent_id' => $agentId]);
            $lease = $leaseStatement->fetch();
            if (!$lease || !$this->boolValue($lease['enabled']) || !$this->boolValue($lease['pool_enabled'])) {
                throw new InvalidArgumentException('active_overlay_lease_required');
            }
            $mappingId = $this->identifier($input['mappingId'] ?? null, 'map');
            $localIP = IPv4::normalizeAddress($this->requiredString($input, 'localIp'));
            $overlayIPValue = trim((string) ($input['overlayIp'] ?? ''));
            if ($overlayIPValue === '') {
                $used = $this->db->prepare('SELECT host(overlay_ip) AS value FROM edge_overlay_mappings WHERE agent_id = :agent_id AND mapping_id <> :mapping_id');
                $used->execute(['agent_id' => $agentId, 'mapping_id' => $mappingId]);
                $overlayIP = IPv4::firstUsableAddress((string) $lease['overlay_prefix'], array_column($used->fetchAll(), 'value'));
            } else {
                $overlayIP = IPv4::normalizeAddress($overlayIPValue);
                if (!IPv4::contains((string) $lease['overlay_prefix'], $overlayIP, true)) {
                    throw new InvalidArgumentException('mapping_overlay_ip_outside_lease');
                }
            }
            $enabled = ($input['enabled'] ?? true) === true;
            $comment = trim((string) ($input['comment'] ?? ''));
            if (strlen($comment) > 500) {
                throw new InvalidArgumentException('mapping_comment_too_long');
            }
            $nextGeneration = (int) $agent['desired_generation'] + 1;
            $statement = $this->db->prepare(<<<'SQL'
                INSERT INTO edge_overlay_mappings (
                    mapping_id, agent_id, local_ip, overlay_ip, enabled, comment,
                    desired_generation, updated_at
                ) VALUES (
                    :mapping_id, :agent_id, CAST(:local_ip AS inet), CAST(:overlay_ip AS inet),
                    CAST(:enabled AS boolean), :comment, :generation, now()
                )
                ON CONFLICT (mapping_id) DO UPDATE SET
                    local_ip = EXCLUDED.local_ip,
                    overlay_ip = EXCLUDED.overlay_ip,
                    enabled = EXCLUDED.enabled,
                    comment = EXCLUDED.comment,
                    desired_generation = EXCLUDED.desired_generation,
                    current_state = 'pending',
                    current_error = NULL,
                    updated_at = now()
                WHERE edge_overlay_mappings.agent_id = EXCLUDED.agent_id
                SQL);
            $statement->execute([
                'mapping_id' => $mappingId,
                'agent_id' => $agentId,
                'local_ip' => $localIP,
                'overlay_ip' => $overlayIP,
                'enabled' => $enabled ? 'true' : 'false',
                'comment' => $comment,
                'generation' => $nextGeneration,
            ]);
            if ($statement->rowCount() !== 1) {
                throw new InvalidArgumentException('mapping_id_belongs_to_another_agent');
            }
            $generation = $this->rebuildDesiredStateLocked($agentId);
            $this->log->write('overlay_mapping_saved', [
                'agentId' => $agentId,
                'mappingId' => $mappingId,
                'localIp' => $localIP,
                'overlayIp' => $overlayIP,
                'desiredGeneration' => $generation,
            ]);
            return $this->agent($agentId);
        });
    }

    /** @return array<string,mixed> */
    public function deleteMapping(string $agentId, string $mappingId): array
    {
        return $this->transaction(function () use ($agentId, $mappingId): array {
            $agent = $this->lockedAgent($agentId, ['active', 'revoking', 'revoked']);
            $active = $agent['pairing_status'] === 'active';
            if ($active) {
                $this->requireManagedScope($agent, 'mapping.configure');
            }
            $statement = $this->db->prepare(
                'DELETE FROM edge_overlay_mappings WHERE agent_id = :agent_id AND mapping_id = :mapping_id'
            );
            $statement->execute(['agent_id' => $agentId, 'mapping_id' => $mappingId]);
            if ($statement->rowCount() !== 1) {
                throw new InvalidArgumentException('mapping_not_found');
            }
            $generation = $active
                ? $this->rebuildDesiredStateLocked($agentId)
                : (int) $agent['desired_generation'];
            $this->log->write('overlay_mapping_deleted', [
                'agentId' => $agentId,
                'mappingId' => $mappingId,
                'desiredGeneration' => $generation,
            ]);
            return $this->agent($agentId);
        });
    }

    /** @return array<string,mixed> */
    public function createInvitation(string $serverURL, int $ttl, string $displayName, ?int $createdBy): array
    {
        $serverURL = rtrim(trim($serverURL), '/');
        if (!preg_match('#^https://[^/]+$#', $serverURL)) {
            throw new InvalidArgumentException('pairing_server_url_invalid');
        }
        $ttl = max(60, min(3600, $ttl));
        $pairingId = $this->identifier(null, 'pair');
        $pairingCode = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
        $expiresAt = $this->now()->modify('+' . $ttl . ' seconds');
        $statement = $this->db->prepare(<<<'SQL'
            INSERT INTO edge_agent_pairing_invitations (
                pairing_id, code_hash, controller_id, expires_at, created_by, metadata
            ) VALUES (
                :pairing_id, :code_hash, :controller_id, :expires_at, :created_by, CAST(:metadata AS jsonb)
            )
            SQL);
        $statement->execute([
            'pairing_id' => $pairingId,
            'code_hash' => hash('sha256', $pairingCode),
            'controller_id' => $this->identity->id(),
            'expires_at' => $expiresAt->format('Y-m-d H:i:sP'),
            'created_by' => $createdBy,
            'metadata' => $this->json(['displayName' => trim($displayName) ?: 'RBT']),
        ]);
        $this->log->write('pairing_invitation_created', ['pairingId' => $pairingId, 'createdBy' => $createdBy]);
        return [
            'schemaVersion' => 1,
            'controllerType' => 'rbt',
            'serverUrl' => $serverURL,
            'controllerId' => $this->identity->id(),
            'controllerPublicKey' => $this->identity->publicKey(),
            'controllerKeyId' => $this->identity->keyId(),
            'pairingCode' => $pairingCode,
            'expiresAt' => $expiresAt->format('Y-m-d\TH:i:s\Z'),
            'displayName' => trim($displayName) ?: 'RBT',
        ];
    }

    /** @return array<string,mixed> */
    public function authorizeManaged(string $agentId, string $secret, int $ttl = 600): array
    {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('managed_secret_required');
        }
        $ttl = max(60, min(1800, $ttl));
        try {
            return $this->transaction(function () use ($agentId, &$secret, $ttl): array {
                $agent = $this->lockedAgent($agentId);
                $management = $this->decodeObject($agent['management_state']);
                if (($management['enabled'] ?? false) !== true || ($management['credentialSet'] ?? false) !== true) {
                    throw new InvalidArgumentException('agent_managed_authorization_not_enabled');
                }
                $revision = $management['credentialRevision'] ?? null;
                if (!is_int($revision) || $revision <= 0) {
                    throw new InvalidArgumentException('agent_management_revision_invalid');
                }
                $scopes = ManagedAuthorization::normalizeScopes(
                    is_array($management['scopes'] ?? null) ? $management['scopes'] : [],
                );
                if ($scopes === []) {
                    throw new InvalidArgumentException('agent_managed_scopes_empty');
                }
                $challenge = Protocol::encodeBase64(random_bytes(32));
                $proof = ManagedAuthorization::proof(
                    $secret,
                    $agentId,
                    $this->identity->id(),
                    $revision,
                    $challenge,
                    $scopes,
                );
                $this->db->prepare(<<<'SQL'
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
                    SQL)->execute([
                        'revision' => $revision,
                        'challenge' => $challenge,
                        'proof' => $proof,
                        'scopes' => $this->json($scopes),
                        'ttl' => $ttl,
                        'agent_id' => $agentId,
                    ]);
                $this->log->write('managed_authorization_requested', [
                    'agentId' => $agentId,
                    'revision' => $revision,
                    'scopes' => $scopes,
                ]);
                return ['ok' => true, 'agentId' => $agentId, 'revision' => $revision, 'scopes' => $scopes];
            });
        } finally {
            if ($secret !== '') {
                sodium_memzero($secret);
            }
        }
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function queueAction(string $agentId, string $type, array $payload, int $ttl = 600): array
    {
        $requirements = [
            'diagnostics' => 'network.diagnose',
            'inventory_refresh' => 'network.diagnose',
            'lan_scan' => 'lan.scan',
            'awg_key_rotation' => 'overlay.configure',
        ];
        $type = trim($type);
        if (!isset($requirements[$type])) {
            throw new InvalidArgumentException('action_type_invalid');
        }
        if ($payload !== []) {
            throw new InvalidArgumentException('action_payload_not_supported');
        }
        $ttl = max(30, min(3600, $ttl));
        return $this->transaction(function () use ($agentId, $type, $payload, $ttl, $requirements): array {
            $agent = $this->lockedAgent($agentId);
            $this->requireManagedScope($agent, $requirements[$type]);
            $actionId = $this->identifier(null, 'act');
            $idempotencyKey = $this->identifier(null, 'idem');
            $statement = $this->db->prepare(<<<'SQL'
                INSERT INTO edge_agent_actions (
                    action_id, agent_id, action_type, payload, idempotency_key, expires_at
                ) VALUES (
                    :action_id, :agent_id, :action_type, CAST(:payload AS jsonb),
                    :idempotency_key, now() + CAST(:ttl AS integer) * interval '1 second'
                )
                RETURNING action_id, action_type, state, payload, idempotency_key, expires_at, created_at
                SQL);
            $statement->execute([
                'action_id' => $actionId,
                'agent_id' => $agentId,
                'action_type' => $type,
                'payload' => $this->json((object) $payload),
                'idempotency_key' => $idempotencyKey,
                'ttl' => $ttl,
            ]);
            $this->log->write('agent_action_queued', [
                'agentId' => $agentId,
                'actionId' => $actionId,
                'type' => $type,
            ]);
            return $this->normalizeRow($statement->fetch());
        });
    }

    /** @return array<string,mixed> */
    public function releasePool(string $agentId): array
    {
        return $this->transaction(function () use ($agentId): array {
            $agent = $this->lockedAgent($agentId, ['active', 'revoking', 'revoked']);
            $active = $agent['pairing_status'] === 'active';
            if ($active) {
                $this->requireManagedScope($agent, 'overlay.configure');
            }
            $mappingCount = $this->db->prepare(
                'SELECT count(*) FROM edge_overlay_mappings WHERE agent_id = :agent_id'
            );
            $mappingCount->execute(['agent_id' => $agentId]);
            if ((int) $mappingCount->fetchColumn() !== 0) {
                throw new InvalidArgumentException('delete_mappings_before_releasing_pool');
            }
            $statement = $this->db->prepare('DELETE FROM edge_overlay_leases WHERE agent_id = :agent_id');
            $statement->execute(['agent_id' => $agentId]);
            if ($statement->rowCount() !== 1) {
                throw new InvalidArgumentException('overlay_lease_not_found');
            }
            $generation = (int) $agent['desired_generation'];
            if ($active) {
                $generation++;
                $this->db->prepare(<<<'SQL'
                    UPDATE edge_agents SET desired_generation = :generation,
                        desired_state = '{"overlay":{},"mappings":[]}'::jsonb,
                        updated_at = now()
                    WHERE agent_id = :agent_id
                    SQL)->execute(['generation' => $generation, 'agent_id' => $agentId]);
            }
            $this->log->write('overlay_lease_released', [
                'agentId' => $agentId,
                'desiredGeneration' => $generation,
            ]);
            return $this->agent($agentId);
        });
    }

    /** @return array{ok:bool,agentId:string} */
    public function revokeAgent(string $agentId, string $reason = 'revoked_by_rbt_admin'): array
    {
        $reason = trim($reason);
        if ($reason === '' || strlen($reason) > 256) {
            throw new InvalidArgumentException('revocation_reason_invalid');
        }
        return $this->transaction(function () use ($agentId, $reason): array {
            $statement = $this->db->prepare('SELECT * FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE');
            $statement->execute(['agent_id' => $agentId]);
            $agent = $statement->fetch();
            if (!$agent) {
                throw new InvalidArgumentException('agent_not_found');
            }
            if ($agent['pairing_status'] === 'revoked') {
                return ['ok' => true, 'agentId' => (string) $agent['agent_id'], 'status' => 'revoked'];
            }
            $this->db->prepare(<<<'SQL'
                UPDATE edge_agents SET pairing_status = 'revoking', revoked_at = NULL,
                    revocation_requested_at = COALESCE(revocation_requested_at, now()),
                    revocation_reason = :reason,
                    managed_authorized = false, managed_scopes = '[]'::jsonb,
                    management_challenge = NULL, management_proof = NULL,
                    management_requested_scopes = '[]'::jsonb, management_expires_at = NULL,
                    desired_generation = desired_generation + 1,
                    desired_state = '{"overlay":{},"mappings":[]}'::jsonb,
                    updated_at = now()
                WHERE agent_id = :agent_id
                SQL)->execute(['agent_id' => $agentId, 'reason' => $reason]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_overlay_leases SET enabled = false, gateway_state = 'pending',
                    gateway_error = NULL, updated_at = now()
                WHERE agent_id = :agent_id
                SQL)->execute(['agent_id' => $agentId]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_overlay_mappings SET current_state = 'pending',
                    current_error = :reason, updated_at = now()
                WHERE agent_id = :agent_id
                SQL)->execute(['agent_id' => $agentId, 'reason' => $reason]);
            $this->db->prepare(<<<'SQL'
                UPDATE edge_agent_actions SET state = 'expired', error = :reason,
                    completed_at = now(), updated_at = now()
                WHERE agent_id = :agent_id AND state IN ('queued', 'running')
                SQL)->execute(['agent_id' => $agentId, 'reason' => $reason]);
            $this->log->write('agent_pairing_revocation_requested', ['agentId' => $agentId, 'reason' => $reason]);
            return ['ok' => true, 'agentId' => (string) $agent['agent_id'], 'status' => 'revoking'];
        });
    }

    private function rebuildPoolAgents(string $poolId): void
    {
        $statement = $this->db->prepare('SELECT agent_id FROM edge_overlay_leases WHERE pool_id = :pool_id');
        $statement->execute(['pool_id' => $poolId]);
        foreach ($statement->fetchAll() as $row) {
            $this->transaction(fn(): int => $this->rebuildDesiredStateLocked((string) $row['agent_id']));
        }
    }

    private function rebuildDesiredStateLocked(string $agentId): int
    {
        $agent = $this->lockedAgent($agentId);
        $leaseStatement = $this->db->prepare(<<<'SQL'
            SELECT l.lease_id, l.agent_id, l.pool_id,
                   host(l.tunnel_ip) AS tunnel_ip,
                   l.overlay_prefix::text AS overlay_prefix,
                   l.agent_public_key, l.enabled AS lease_enabled,
                   p.enabled AS pool_enabled, p.overlay_type, p.gateway_endpoint,
                   p.gateway_public_key, p.allowed_source_prefixes,
                   p.persistent_keepalive_sec, p.parameters
            FROM edge_overlay_leases l
            JOIN edge_overlay_pools p ON p.pool_id = l.pool_id
            WHERE l.agent_id = :agent_id
            FOR UPDATE OF l, p
            SQL);
        $leaseStatement->execute(['agent_id' => $agentId]);
        $lease = $leaseStatement->fetch();
        $mappings = [];
        $overlay = (object) [];
        if ($lease) {
            $enabled = $this->boolValue($lease['lease_enabled']) && $this->boolValue($lease['pool_enabled']);
            $overlay = [
                'enabled' => $enabled,
                'type' => $lease['overlay_type'],
                'endpoint' => $lease['gateway_endpoint'],
                'serverPublicKey' => $lease['gateway_public_key'],
                'tunnelAddress' => $lease['tunnel_ip'] . '/32',
                'overlayPrefix' => $lease['overlay_prefix'],
                'allowedSourcePrefixes' => $this->decodeList($lease['allowed_source_prefixes']),
                'persistentKeepaliveSec' => (int) $lease['persistent_keepalive_sec'],
                'parameters' => $this->decodeObject($lease['parameters']),
            ];
            $mappingStatement = $this->db->prepare(<<<'SQL'
                SELECT mapping_id, host(local_ip) AS local_ip,
                       host(overlay_ip) AS overlay_ip, enabled, comment
                FROM edge_overlay_mappings
                WHERE agent_id = :agent_id
                ORDER BY mapping_id
                FOR UPDATE
                SQL);
            $mappingStatement->execute(['agent_id' => $agentId]);
            foreach ($mappingStatement->fetchAll() as $mapping) {
                $mappings[] = [
                    'id' => $mapping['mapping_id'],
                    'localIp' => $mapping['local_ip'],
                    'overlayIp' => $mapping['overlay_ip'],
                    'enabled' => $this->boolValue($mapping['enabled']),
                    'comment' => $mapping['comment'],
                ];
            }
        }
        $generation = (int) $agent['desired_generation'] + 1;
        $desired = ['overlay' => $overlay, 'mappings' => $mappings];
        $this->db->prepare(<<<'SQL'
            UPDATE edge_agents SET desired_generation = :generation,
                desired_state = CAST(:desired_state AS jsonb), updated_at = now()
            WHERE agent_id = :agent_id
            SQL)->execute([
                'generation' => $generation,
                'desired_state' => $this->json($desired),
                'agent_id' => $agentId,
            ]);
        $this->db->prepare(<<<'SQL'
            UPDATE edge_overlay_leases SET desired_generation = :generation,
                gateway_state = 'pending', gateway_error = NULL, updated_at = now()
            WHERE agent_id = :agent_id
            SQL)->execute(['generation' => $generation, 'agent_id' => $agentId]);
        $this->db->prepare(<<<'SQL'
            UPDATE edge_overlay_mappings SET desired_generation = :generation,
                current_state = 'pending', current_error = NULL, updated_at = now()
            WHERE agent_id = :agent_id
            SQL)->execute(['generation' => $generation, 'agent_id' => $agentId]);
        return $generation;
    }

    /** @return array<string,mixed> */
    /** @param list<string> $allowedStatuses @return array<string,mixed> */
    private function lockedAgent(string $agentId, array $allowedStatuses = ['active']): array
    {
        $statement = $this->db->prepare('SELECT * FROM edge_agents WHERE agent_id = :agent_id FOR UPDATE');
        $statement->execute(['agent_id' => $agentId]);
        $agent = $statement->fetch();
        if (!$agent || !in_array($agent['pairing_status'], $allowedStatuses, true)) {
            throw new InvalidArgumentException('active_agent_not_found');
        }
        return $agent;
    }

    /** @return array<string,mixed> */
    private function lockedPool(string $poolId): array
    {
        $statement = $this->db->prepare('SELECT * FROM edge_overlay_pools WHERE pool_id = :pool_id FOR UPDATE');
        $statement->execute(['pool_id' => $poolId]);
        $pool = $statement->fetch();
        if (!$pool) {
            throw new InvalidArgumentException('overlay_pool_not_found');
        }
        return $pool;
    }

    /** @param array<string,mixed> $agent */
    private function requireManagedScope(array $agent, string $scope): void
    {
        if (!$this->boolValue($agent['managed_authorized'])) {
            throw new InvalidArgumentException('managed_authorization_required');
        }
        if (!in_array($scope, $this->decodeList($agent['managed_scopes']), true)) {
            throw new InvalidArgumentException('managed_scope_required_' . str_replace('.', '_', $scope));
        }
    }

    /** @param callable():mixed $callback */
    private function transaction(callable $callback): mixed
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $result = $callback();
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $result;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizeAgentRow(array $row): array
    {
        $normalized = $this->normalizeRow($row);
        foreach (['capabilities', 'management_state', 'managed_scopes', 'desired_state', 'actual_state', 'health', 'condition_summary'] as $field) {
            if (array_key_exists($field, $row)) {
                $normalized[$field] = json_decode((string) $row[$field], true) ?? [];
            }
        }
        return $normalized;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function normalizeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (in_array($key, ['enabled', 'lease_enabled', 'managed_authorized'], true)) {
                $row[$key] = $this->boolValue($value);
            } elseif (in_array($key, ['allowed_source_prefixes', 'parameters', 'payload', 'result'], true) && is_string($value)) {
                $row[$key] = json_decode($value, true) ?? [];
            }
        }
        return $row;
    }

    /** @param array<string,mixed> $source */
    private function requiredString(array $source, string $key): string
    {
        $value = $source[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException($key . '_required');
        }
        return trim($value);
    }

    private function identifier(mixed $value, string $prefix): string
    {
        if ($value === null || trim((string) $value) === '') {
            return $prefix . '_' . rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }
        $value = trim((string) $value);
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,127}$/', $value)) {
            throw new InvalidArgumentException($prefix . '_id_invalid');
        }
        return $value;
    }

    private function interfaceName(mixed $value): string
    {
        $value = trim((string) $value);
        if (!preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $value)) {
            throw new InvalidArgumentException('gateway_interface_invalid');
        }
        return $value;
    }

    private function integer(mixed $value, int $minimum, int $maximum, string $name): int
    {
        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }
        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            throw new InvalidArgumentException($name . '_invalid');
        }
        return $value;
    }

    /** @return list<string> */
    private function prefixList(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[\s,]+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException('prefix_list_invalid');
        }
        $result = [];
        foreach ($value as $prefix) {
            if (!is_string($prefix)) {
                throw new InvalidArgumentException('prefix_list_invalid');
            }
            $result[IPv4::parsePrefix($prefix, 0, 32)['canonical']] = true;
        }
        $values = array_keys($result);
        sort($values, SORT_STRING);
        return $values;
    }

    /** @return array<string,int> */
    private function awgParameters(mixed $value): array
    {
        if (!is_array($value) || ($value !== [] && array_is_list($value))) {
            throw new InvalidArgumentException('awg_parameters_invalid');
        }
        $allowed = ['jc', 'jmin', 'jmax', 's1', 's2', 'h1', 'h2', 'h3', 'h4'];
        $result = [];
        foreach ($value as $key => $item) {
            $key = strtolower(trim((string) $key));
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException('awg_parameter_invalid_' . $key);
            }
            $result[$key] = $this->integer($item, 0, 0xffffffff, $key);
        }
        ksort($result, SORT_STRING);
        return $result;
    }

    private function wireGuardPublicKey(string $value): string
    {
        $decoded = base64_decode(trim($value), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new InvalidArgumentException('wireguard_public_key_invalid');
        }
        return trim($value);
    }

    private function validEndpoint(string $value): bool
    {
        if (preg_match('/^\[[0-9a-fA-F:]+\]:[1-9][0-9]{0,4}$/', $value)) {
            return (int) substr($value, strrpos($value, ':') + 1) <= 65535;
        }
        if (!preg_match('/^[A-Za-z0-9.-]+:([1-9][0-9]{0,4})$/', $value, $matches)) {
            return false;
        }
        return (int) $matches[1] <= 65535;
    }

    /** @return array<string,mixed> */
    private function decodeObject(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) && !array_is_list($decoded) ? $decoded : [];
    }

    /** @return list<mixed> */
    private function decodeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    private function boolValue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    }

    /** @param array<mixed>|object $value */
    private function json(array|object $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}

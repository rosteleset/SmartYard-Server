<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use PDO;
use RuntimeException;
use Throwable;

final class GatewayReconciler
{
    private const STATE_SCHEMA = 1;

    public function __construct(
        private readonly PDO $db,
        private readonly JsonLog $log,
        private readonly string $stateDirectory,
        private readonly GatewayCommandRunnerInterface $runner,
        private readonly GatewayKeyStore $keys,
    ) {
    }

    /** @return array{ok:bool,pools:list<array<string,mixed>>} */
    public function reconcileAll(bool $dryRun = false): array
    {
        $previous = $this->loadState();
        $plans = $this->loadPlans();
        $results = [];
        $next = $previous;

        foreach ($plans as $poolId => $plan) {
            $previousPlan = $previous[$poolId] ?? null;
            try {
                if ($plan['enabled']) {
                    $this->applyPlan($plan, $previousPlan, $dryRun);
                    if (!$dryRun) {
                        $next[$poolId] = $plan;
                        $this->setGatewayStatus($poolId, 'ready', null);
                    }
                    $results[] = ['poolId' => $poolId, 'state' => $dryRun ? 'validated' : 'ready'];
                } else {
                    if ($previousPlan !== null) {
                        $this->disablePlan($previousPlan, $dryRun);
                    }
                    if (!$dryRun) {
                        unset($next[$poolId]);
                        $this->setGatewayStatus($poolId, 'disabled', null);
                    }
                    $results[] = ['poolId' => $poolId, 'state' => 'disabled'];
                }
            } catch (Throwable $error) {
                $reason = $error->getMessage();
                if (!$dryRun) {
                    try {
                        if ($previousPlan !== null && ($previousPlan['enabled'] ?? false)) {
                            $this->restorePlan($previousPlan, $plan);
                        } else {
                            $this->disablePlan($plan, false);
                        }
                    } catch (Throwable $rollbackError) {
                        $reason .= '; rollback failed: ' . $rollbackError->getMessage();
                        $this->log->write('gateway_rollback_failed', [
                            'poolId' => $poolId,
                            'reason' => $rollbackError->getMessage(),
                        ]);
                    }
                }
                if (!$dryRun) {
                    $this->setGatewayStatus($poolId, 'error', $reason);
                }
                $this->log->write('gateway_reconcile_failed', [
                    'poolId' => $poolId,
                    'reason' => $reason,
                ]);
                $results[] = ['poolId' => $poolId, 'state' => 'error', 'error' => $reason];
            }
        }

        foreach (array_diff(array_keys($previous), array_keys($plans)) as $poolId) {
            try {
                $this->disablePlan($previous[$poolId], $dryRun);
                if (!$dryRun) {
                    unset($next[$poolId]);
                }
                $results[] = ['poolId' => $poolId, 'state' => 'removed'];
            } catch (Throwable $error) {
                $results[] = ['poolId' => $poolId, 'state' => 'error', 'error' => $error->getMessage()];
            }
        }

        if (!$dryRun) {
            $this->persistState($next);
            $this->refreshMappingStates();
        }
        return [
            'ok' => !array_filter($results, static fn(array $result): bool => $result['state'] === 'error'),
            'pools' => $results,
        ];
    }

    /** @return array<string,array<string,mixed>> */
    private function loadPlans(): array
    {
        $rows = $this->db->query(<<<'SQL'
            SELECT p.pool_id, p.enabled AS pool_enabled, p.tunnel_pool::text AS tunnel_pool,
                   p.gateway_endpoint, host(p.gateway_tunnel_address) AS gateway_tunnel_address,
                   p.gateway_public_key, p.gateway_interface, p.overlay_type,
                   p.allowed_source_prefixes, p.parameters,
                   l.agent_id, host(l.tunnel_ip) AS tunnel_ip,
                   l.overlay_prefix::text AS overlay_prefix, l.agent_public_key,
                   l.enabled AS lease_enabled
            FROM edge_overlay_pools p
            LEFT JOIN edge_overlay_leases l ON l.pool_id = p.pool_id
            ORDER BY p.pool_id, l.agent_id
            SQL)->fetchAll();
        $plans = [];
        foreach ($rows as $row) {
            $poolId = (string) $row['pool_id'];
            if (!isset($plans[$poolId])) {
                [$endpointHost, $listenPort] = $this->splitEndpoint((string) $row['gateway_endpoint']);
                $interface = $this->interfaceName((string) $row['gateway_interface']);
                $overlayType = (string) $row['overlay_type'];
                if (!in_array($overlayType, ['wireguard', 'amneziawg'], true)) {
                    throw new RuntimeException("pool $poolId has unsupported overlay type");
                }
                $plans[$poolId] = [
                    'poolId' => $poolId,
                    'enabled' => $this->boolValue($row['pool_enabled']),
                    'interface' => $interface,
                    'overlayType' => $overlayType,
                    'tool' => $overlayType === 'amneziawg' ? 'awg' : 'wg',
                    'linkType' => $overlayType === 'amneziawg' ? 'amneziawg' : 'wireguard',
                    'listenPort' => $listenPort,
                    'endpointHost' => $endpointHost,
                    'gatewayAddress' => IPv4::normalizeAddress((string) $row['gateway_tunnel_address']) . '/' . IPv4::parsePrefix((string) $row['tunnel_pool'])['bits'],
                    'gatewayPublicKey' => (string) $row['gateway_public_key'],
                    'allowedSourcePrefixes' => $this->decodeList($row['allowed_source_prefixes']),
                    'parameters' => $this->decodeObject($row['parameters']),
                    'peers' => [],
                    'routes' => [],
                ];
            }
            if (!is_string($row['agent_id']) || $row['agent_id'] === '' || !$this->boolValue($row['lease_enabled'])) {
                continue;
            }
            if (!is_string($row['agent_public_key']) || trim($row['agent_public_key']) === '') {
                throw new RuntimeException("agent {$row['agent_id']} has no overlay public key");
            }
            $this->wireGuardKey((string) $row['agent_public_key']);
            $tunnelRoute = IPv4::normalizeAddress((string) $row['tunnel_ip']) . '/32';
            $overlayRoute = IPv4::parsePrefix((string) $row['overlay_prefix'])['canonical'];
            $plans[$poolId]['peers'][] = [
                'agentId' => (string) $row['agent_id'],
                'publicKey' => trim((string) $row['agent_public_key']),
                'allowedIPs' => [$tunnelRoute, $overlayRoute],
            ];
            $plans[$poolId]['routes'][] = $tunnelRoute;
            $plans[$poolId]['routes'][] = $overlayRoute;
        }
        foreach ($plans as &$plan) {
            sort($plan['routes'], SORT_STRING);
            usort($plan['peers'], static fn(array $a, array $b): int => strcmp($a['agentId'], $b['agentId']));
        }
        unset($plan);
        return $plans;
    }

    /** @param array<string,mixed> $plan */
    private function applyPlan(array $plan, ?array $previous, bool $dryRun, bool $writeLog = true): void
    {
        foreach (['ip', 'nft', $plan['tool']] as $command) {
            if ($this->runner->find((string) $command) === null) {
                throw new RuntimeException("required command $command is not available");
            }
        }
        $key = $dryRun
            ? $this->keys->load((string) $plan['interface'], (string) $plan['tool'])
            : $this->keys->ensure((string) $plan['interface'], (string) $plan['tool']);
        if (!hash_equals((string) $plan['gatewayPublicKey'], $key['publicKey'])) {
            throw new RuntimeException('configured gateway public key does not match local private key');
        }
        $config = self::renderInterfaceConfig($plan, trim((string) file_get_contents($key['privateKeyPath'])));
        if ($dryRun) {
            return;
        }
        $interface = (string) $plan['interface'];
        if ($previous !== null &&
            (string) ($previous['interface'] ?? '') === $interface &&
            (string) ($previous['linkType'] ?? '') !== (string) $plan['linkType']) {
            $this->disablePlan($previous, false, false);
            $previous = null;
        }
        if (!$this->commandSucceeds('ip', ['link', 'show', 'dev', $interface])) {
            $this->runner->run('ip', ['link', 'add', 'dev', $interface, 'type', (string) $plan['linkType']]);
        }
        $configurationFile = $this->temporaryConfig($config);
        try {
            $this->runner->run((string) $plan['tool'], ['syncconf', $interface, $configurationFile]);
        } finally {
            @unlink($configurationFile);
        }
        $this->runner->run('ip', ['address', 'replace', (string) $plan['gatewayAddress'], 'dev', $interface]);
        $this->runner->run('ip', ['link', 'set', 'up', 'dev', $interface]);
        foreach ($plan['routes'] as $route) {
            $this->runner->run('ip', ['route', 'replace', (string) $route, 'dev', $interface]);
        }
        $this->replaceFirewall($plan);
        $this->runner->run((string) $plan['tool'], ['show', $interface]);
        $this->removeStaleConfiguration($previous, $plan);
        if ($writeLog) {
            $this->log->write('gateway_reconciled', [
                'poolId' => $plan['poolId'],
                'interface' => $interface,
                'peerCount' => count($plan['peers']),
            ]);
        }
    }

    /** @param array<string,mixed> $plan */
    private function disablePlan(array $plan, bool $dryRun, bool $writeLog = true): void
    {
        if ($dryRun) {
            return;
        }
        $interface = $this->interfaceName((string) $plan['interface']);
        $table = self::firewallTable($interface);
        if ($this->commandSucceeds('nft', ['list', 'table', 'inet', $table])) {
            $this->runner->run('nft', ['delete', 'table', 'inet', $table]);
        }
        if ($this->commandSucceeds('ip', ['link', 'show', 'dev', $interface])) {
            $this->runner->run('ip', ['link', 'delete', 'dev', $interface]);
        }
        if ($writeLog) {
            $this->log->write('gateway_disabled', ['poolId' => $plan['poolId'] ?? null, 'interface' => $interface]);
        }
    }

    /** @param array<string,mixed> $previous @param array<string,mixed> $failed */
    private function restorePlan(array $previous, array $failed): void
    {
        if ((string) ($failed['interface'] ?? '') !== (string) ($previous['interface'] ?? '') ||
            (string) ($failed['linkType'] ?? '') !== (string) ($previous['linkType'] ?? '')) {
            $this->disablePlan($failed, false, false);
        }
        $this->applyPlan($previous, $failed, false, false);
        $this->log->write('gateway_rollback_completed', [
            'poolId' => $previous['poolId'] ?? null,
            'interface' => $previous['interface'] ?? null,
        ]);
    }

    /** @param array<string,mixed>|null $previous @param array<string,mixed> $current */
    private function removeStaleConfiguration(?array $previous, array $current): void
    {
        if ($previous === null) {
            return;
        }
        $oldInterface = (string) ($previous['interface'] ?? '');
        $newInterface = (string) $current['interface'];
        if ($oldInterface !== $newInterface) {
            $this->disablePlan($previous, false, false);
            return;
        }
        foreach (is_array($previous['routes'] ?? null) ? $previous['routes'] : [] as $route) {
            if (!in_array($route, $current['routes'], true)) {
                $this->commandSucceeds('ip', ['route', 'delete', (string) $route, 'dev', $newInterface]);
            }
        }
        $oldAddress = (string) ($previous['gatewayAddress'] ?? '');
        if ($oldAddress !== '' && $oldAddress !== (string) $current['gatewayAddress']) {
            $this->commandSucceeds('ip', ['address', 'delete', $oldAddress, 'dev', $newInterface]);
        }
    }

    /** @param array<string,mixed> $plan */
    public static function renderInterfaceConfig(array $plan, string $privateKey): string
    {
        $lines = [
            '[Interface]',
            'PrivateKey = ' . trim($privateKey),
            'ListenPort = ' . (int) $plan['listenPort'],
        ];
        $parameterNames = [
            'jc' => 'Jc', 'jmin' => 'Jmin', 'jmax' => 'Jmax',
            's1' => 'S1', 's2' => 'S2', 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4',
        ];
        $parameters = is_array($plan['parameters'] ?? null) ? $plan['parameters'] : [];
        ksort($parameters, SORT_STRING);
        foreach ($parameters as $name => $value) {
            $normalized = strtolower((string) $name);
            if (!isset($parameterNames[$normalized]) || !is_int($value) || $value < 0) {
                throw new RuntimeException("invalid AmneziaWG parameter $name");
            }
            $lines[] = $parameterNames[$normalized] . ' = ' . $value;
        }
        foreach ($plan['peers'] ?? [] as $peer) {
            $lines[] = '';
            $lines[] = '[Peer]';
            $lines[] = 'PublicKey = ' . trim((string) $peer['publicKey']);
            $lines[] = 'AllowedIPs = ' . implode(', ', $peer['allowedIPs']);
        }
        return implode("\n", $lines) . "\n";
    }

    /** @param array<string,mixed> $plan */
    private function replaceFirewall(array $plan): void
    {
        $interface = (string) $plan['interface'];
        $table = self::firewallTable($interface);
        $exists = $this->commandSucceeds('nft', ['list', 'table', 'inet', $table]);
        $sources = $plan['allowedSourcePrefixes'];
        if (!is_array($sources) || $sources === []) {
            throw new RuntimeException('gateway allowed source prefixes are empty');
        }
        $sourceSet = implode(', ', array_map(
            static fn(string $prefix): string => IPv4::parsePrefix($prefix)['canonical'],
            $sources,
        ));
        $script = $exists ? "delete table inet $table\n" : '';
        $script .= "table inet $table {\n";
        $script .= "  chain input { type filter hook input priority -5; policy accept; udp dport " . (int) $plan['listenPort'] . " accept; }\n";
        $script .= "  chain forward { type filter hook forward priority -5; policy accept;\n";
        $script .= "    oifname \"$interface\" ip saddr { $sourceSet } accept\n";
        $script .= "    oifname \"$interface\" drop\n";
        $script .= "    iifname \"$interface\" ct state established,related accept\n";
        $script .= "    iifname \"$interface\" drop\n";
        $script .= "  }\n}\n";
        $this->runner->run('nft', ['-f', '-'], $script);
    }

    private static function firewallTable(string $interface): string
    {
        return 'rbt_edge_' . substr(hash('sha256', $interface), 0, 12);
    }

    private function setGatewayStatus(string $poolId, string $state, ?string $error): void
    {
        $statement = $this->db->prepare(<<<'SQL'
            UPDATE edge_overlay_leases SET gateway_state = :state,
                gateway_error = :error,
                gateway_applied_at = CASE WHEN :state = 'ready' THEN now() ELSE gateway_applied_at END,
                updated_at = now()
            WHERE pool_id = :pool_id
            SQL);
        $statement->execute(['state' => $state, 'error' => $error, 'pool_id' => $poolId]);
    }

    private function refreshMappingStates(): void
    {
        $rows = $this->db->query(<<<'SQL'
            SELECT m.mapping_id, m.desired_generation, a.applied_generation,
                   a.actual_state, l.gateway_state
            FROM edge_overlay_mappings m
            JOIN edge_agents a ON a.agent_id = m.agent_id
            JOIN edge_overlay_leases l ON l.agent_id = m.agent_id
            SQL)->fetchAll();
        $update = $this->db->prepare(<<<'SQL'
            UPDATE edge_overlay_mappings SET current_state = :state,
                current_error = :error, updated_at = now()
            WHERE mapping_id = :mapping_id
            SQL);
        foreach ($rows as $row) {
            $actual = json_decode((string) $row['actual_state'], true);
            $reported = [];
            foreach (is_array($actual['mappings'] ?? null) ? $actual['mappings'] : [] as $mapping) {
                if (is_array($mapping) && is_string($mapping['id'] ?? null)) {
                    $reported[$mapping['id']] = $mapping;
                }
            }
            $mapping = $reported[(string) $row['mapping_id']] ?? null;
            $generationReady = (int) $row['applied_generation'] >= (int) $row['desired_generation'];
            $agentState = is_array($mapping) ? (string) ($mapping['state'] ?? '') : '';
            $agentError = is_array($mapping) ? trim((string) ($mapping['error'] ?? '')) : '';
            if ($agentError !== '' || in_array($agentState, ['error', 'failed'], true)) {
                $state = 'error';
                $error = $agentError ?: 'agent_mapping_failed';
            } elseif ($row['gateway_state'] === 'ready' && $generationReady && in_array($agentState, ['applied', 'ready', 'active'], true)) {
                $state = 'active';
                $error = null;
            } else {
                $state = 'pending';
                $error = null;
            }
            $update->execute(['state' => $state, 'error' => $error, 'mapping_id' => $row['mapping_id']]);
        }
    }

    /** @return array<string,array<string,mixed>> */
    private function loadState(): array
    {
        $path = $this->statePath();
        if (!is_file($path)) {
            return [];
        }
        $document = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($document) || ($document['schemaVersion'] ?? null) !== self::STATE_SCHEMA || !is_array($document['pools'] ?? null)) {
            throw new RuntimeException('gateway state file is invalid');
        }
        return $document['pools'];
    }

    /** @param array<string,array<string,mixed>> $pools */
    private function persistState(array $pools): void
    {
        if (!is_dir($this->stateDirectory) && !mkdir($this->stateDirectory, 0700, true) && !is_dir($this->stateDirectory)) {
            throw new RuntimeException("cannot create gateway state directory {$this->stateDirectory}");
        }
        $data = json_encode(
            ['schemaVersion' => self::STATE_SCHEMA, 'updatedAt' => gmdate('c'), 'pools' => $pools],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ) . "\n";
        $temporary = $this->statePath() . '.tmp.' . getmypid();
        if (file_put_contents($temporary, $data, LOCK_EX) === false) {
            throw new RuntimeException('cannot write gateway state');
        }
        chmod($temporary, 0600);
        if (!rename($temporary, $this->statePath())) {
            @unlink($temporary);
            throw new RuntimeException('cannot install gateway state');
        }
    }

    private function statePath(): string
    {
        return rtrim($this->stateDirectory, '/') . '/applied-state.json';
    }

    private function temporaryConfig(string $content): string
    {
        if (!is_dir($this->stateDirectory) && !mkdir($this->stateDirectory, 0700, true) && !is_dir($this->stateDirectory)) {
            throw new RuntimeException("cannot create gateway state directory {$this->stateDirectory}");
        }
        $path = tempnam($this->stateDirectory, 'gateway-conf-');
        if ($path === false || file_put_contents($path, $content, LOCK_EX) === false) {
            throw new RuntimeException('cannot create temporary gateway config');
        }
        chmod($path, 0600);
        return $path;
    }

    /** @return array{0:string,1:int} */
    private function splitEndpoint(string $endpoint): array
    {
        if (preg_match('/^\[([^]]+)]:(\d+)$/', $endpoint, $matches)) {
            $host = $matches[1];
            $port = (int) $matches[2];
        } elseif (preg_match('/^([^:]+):(\d+)$/', $endpoint, $matches)) {
            $host = $matches[1];
            $port = (int) $matches[2];
        } else {
            throw new RuntimeException("invalid gateway endpoint $endpoint");
        }
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException("invalid gateway endpoint port $port");
        }
        return [$host, $port];
    }

    private function interfaceName(string $value): string
    {
        if (!preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $value)) {
            throw new RuntimeException('gateway interface name is invalid');
        }
        return $value;
    }

    private function wireGuardKey(string $value): void
    {
        $decoded = base64_decode(trim($value), true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException('WireGuard public key is invalid');
        }
    }

    /** @return list<string> */
    private function decodeList(mixed $value): array
    {
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new RuntimeException('JSON list is invalid');
        }
        return array_values(array_map(static fn(mixed $item): string => (string) $item, $decoded));
    }

    /** @return array<string,int> */
    private function decodeObject(mixed $value): array
    {
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);
        if ($decoded === []) {
            return [];
        }
        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new RuntimeException('JSON object is invalid');
        }
        $result = [];
        foreach ($decoded as $key => $item) {
            if (!is_int($item) || $item < 0) {
                throw new RuntimeException("invalid AmneziaWG parameter $key");
            }
            $result[strtolower((string) $key)] = $item;
        }
        return $result;
    }

    /** @param list<string> $arguments */
    private function commandSucceeds(string $command, array $arguments): bool
    {
        try {
            $this->runner->run($command, $arguments);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function boolValue(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 't' || $value === 'true';
    }
}

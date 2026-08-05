<?php

declare(strict_types=1);

use SesameWare\RbtAgent\Controller;
use SesameWare\RbtAgent\ControllerIdentity;
use SesameWare\RbtAgent\ControllerResponse;
use SesameWare\RbtAgent\DesiredStateService;
use SesameWare\RbtAgent\GatewayCommandRunnerInterface;
use SesameWare\RbtAgent\GatewayKeyStore;
use SesameWare\RbtAgent\GatewayReconciler;
use SesameWare\RbtAgent\JsonLog;
use SesameWare\RbtAgent\Protocol;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/Protocol.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/ManagedAuthorization.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/ControllerIdentity.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/JsonLog.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/IPv4.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/DesiredStateService.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/Controller.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayCommandRunner.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayKeyStore.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayReconciler.php';

final class IntegrationGatewayRunner implements GatewayCommandRunnerInterface
{
    /** @var list<array{command:string,arguments:list<string>,stdin:string,config:?string}> */
    public array $calls = [];

    public function __construct(private readonly string $publicKey)
    {
    }

    public function find(string $command): ?string
    {
        return in_array($command, ['ip', 'nft', 'wg'], true) ? '/fake/' . $command : null;
    }

    public function run(string $command, array $arguments = [], string $stdin = ''): string
    {
        $config = null;
        if ($command === 'wg' && ($arguments[0] ?? null) === 'syncconf' && is_file($arguments[2] ?? '')) {
            $config = (string) file_get_contents($arguments[2]);
        }
        $this->calls[] = compact('command', 'arguments', 'stdin', 'config');

        if ($command === 'wg' && $arguments === ['genkey']) {
            return base64_encode(str_repeat('k', 32)) . "\n";
        }
        if ($command === 'wg' && $arguments === ['pubkey']) {
            return $this->publicKey . "\n";
        }
        if (($command === 'ip' && array_slice($arguments, 0, 3) === ['link', 'show', 'dev']) ||
            ($command === 'nft' && array_slice($arguments, 0, 3) === ['list', 'table', 'inet'])) {
            throw new RuntimeException('not found');
        }
        return '';
    }
}

$dsn = getenv('RBT_TEST_PG_DSN');
if (!is_string($dsn) || $dsn === '') {
    fwrite(STDOUT, "controller_postgres_integration_test: skipped (RBT_TEST_PG_DSN is unset)\n");
    exit(0);
}

$db = new PDO(
    $dsn,
    getenv('RBT_TEST_PG_USER') ?: null,
    getenv('RBT_TEST_PG_PASSWORD') ?: null,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
);
$schema = 'edge_agent_test_' . bin2hex(random_bytes(6));
$root = sys_get_temp_dir() . '/rbt-controller-test-' . bin2hex(random_bytes(5));
mkdir($root, 0700, true);

/** @return array{public:string,secret:string,keyId:string} */
function testIdentity(): array
{
    $pair = sodium_crypto_sign_keypair();
    $public = Protocol::encodeBase64(sodium_crypto_sign_publickey($pair));
    return [
        'public' => $public,
        'secret' => Protocol::encodeBase64(sodium_crypto_sign_secretkey($pair)),
        'keyId' => Protocol::keyId($public),
    ];
}

/** @param array<string,mixed> $payload @param array{public:string,secret:string,keyId:string} $identity @return array<string,mixed> */
function signedControllerCall(
    Controller $controller,
    ControllerIdentity $controllerIdentity,
    string $path,
    array $payload,
    int $sequence,
    string $agentId,
    array $identity,
    int $expectedStatus = 200,
): array {
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $requestId = 'req_' . $sequence . '_' . bin2hex(random_bytes(6));
    $metadata = [
        'direction' => 'request',
        'method' => 'POST',
        'path' => $path,
        'statusCode' => 0,
        'bodySha256' => Protocol::bodySha256($body),
        'signerId' => $agentId,
        'keyId' => $identity['keyId'],
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'requestId' => $requestId,
        'sequence' => $sequence,
    ];
    $headers = [
        'X-RBT-Agent-Signature-Version' => Protocol::VERSION,
        'X-RBT-Agent-Signer-ID' => $agentId,
        'X-RBT-Agent-Key-ID' => $identity['keyId'],
        'X-RBT-Agent-Timestamp' => $metadata['timestamp'],
        'X-RBT-Agent-Request-ID' => $requestId,
        'X-RBT-Agent-Sequence' => (string) $sequence,
        'X-RBT-Agent-Content-SHA256' => $metadata['bodySha256'],
        'X-RBT-Agent-Signature' => Protocol::sign($identity['secret'], $metadata),
    ];
    $response = $controller->handle('POST', $path, $headers, $body, '127.0.0.1');
    if (!$response instanceof ControllerResponse || $response->status !== $expectedStatus) {
        throw new RuntimeException("$path returned {$response->status}: {$response->body}");
    }
    $responseMetadata = [
        'direction' => 'response',
        'method' => 'POST',
        'path' => $path,
        'statusCode' => $response->status,
        'bodySha256' => $response->headers['X-RBT-Agent-Content-SHA256'],
        'signerId' => $response->headers['X-RBT-Agent-Signer-ID'],
        'keyId' => $response->headers['X-RBT-Agent-Key-ID'],
        'timestamp' => $response->headers['X-RBT-Agent-Timestamp'],
        'requestId' => $response->headers['X-RBT-Agent-Request-ID'],
        'sequence' => (int) $response->headers['X-RBT-Agent-Sequence'],
    ];
    Protocol::verify(
        $controllerIdentity->publicKey(),
        $responseMetadata,
        $response->headers['X-RBT-Agent-Signature'],
        $response->body,
    );
    return json_decode($response->body, true, flags: JSON_THROW_ON_ERROR);
}

try {
    $db->exec('CREATE SCHEMA "' . $schema . '"');
    $db->exec('SET search_path TO "' . $schema . '", public');
    $migration = __DIR__ . '/../../server/data/pgsql/v95_edge_agents.sql';
    $db->exec((string) file_get_contents($migration));

    $controllerIdentity = ControllerIdentity::loadOrCreate($root . '/controller-identity.json', 'rbt-test-controller');
    $log = new JsonLog($root . '/events.jsonl');
    $controller = new Controller($db, $controllerIdentity, $log);
    $service = new DesiredStateService($db, $controllerIdentity, $log);
    $agentIdentity = testIdentity();
    $agentId = 'agent-integration-test';
    $agentChallenge = Protocol::encodeBase64(random_bytes(32));
    $invitation = $service->createInvitation('https://rbt.example', 600, 'RBT test', null);

    $pair = signedControllerCall($controller, $controllerIdentity, '/rbt-agent/v2/pair', [
        'schemaVersion' => 1,
        'pairingCode' => $invitation['pairingCode'],
        'agent' => [
            'agentId' => $agentId,
            'agentName' => 'Integration agent',
            'version' => 'test',
            'identity' => [
                'algorithm' => 'ed25519',
                'publicKey' => $agentIdentity['public'],
                'keyId' => $agentIdentity['keyId'],
            ],
            'capabilities' => ['rbt_signed_https_v2'],
        ],
        'agentChallenge' => $agentChallenge,
    ], 1, $agentId, $agentIdentity);
    signedControllerCall($controller, $controllerIdentity, '/rbt-agent/v2/pair/confirm', [
        'schemaVersion' => 1,
        'pairingId' => $pair['pairingId'],
        'agentId' => $agentId,
        'agentChallenge' => $agentChallenge,
        'controllerChallenge' => $pair['controllerChallenge'],
    ], 2, $agentId, $agentIdentity);

    $management = [
        'enabled' => true,
        'authorized' => false,
        'credentialSet' => true,
        'credentialRevision' => 1,
        'scopes' => ['overlay.configure', 'mapping.configure', 'network.diagnose', 'lan.scan'],
    ];
    $overlayKey = base64_encode(str_repeat('a', 32));
    $syncPayload = static function (array $management, int $observed = 0, int $applied = 0) use ($agentId, $agentIdentity, $overlayKey): array {
        return [
            'schemaVersion' => 1,
            'agentId' => $agentId,
            'agentName' => 'Integration agent',
            'agentVersion' => 'test',
            'identity' => ['publicKey' => $agentIdentity['public'], 'keyId' => $agentIdentity['keyId']],
            'capabilities' => [
                'protocol' => ['rbt_signed_https_v2'],
                'overlayTypes' => ['wireguard'],
                'overlayPublicKey' => $overlayKey,
                'fullNat44' => true,
                'atomicNftablesReplace' => true,
                'lanScan' => true,
            ],
            'management' => $management,
            'observedGeneration' => $observed,
            'appliedGeneration' => $applied,
            'actualState' => ['overlay' => ['state' => 'disabled'], 'mappings' => [], 'conditions' => []],
            'health' => ['uptimeSec' => 10],
        ];
    };
    signedControllerCall($controller, $controllerIdentity, '/rbt-agent/v2/sync', $syncPayload($management), 3, $agentId, $agentIdentity);

    try {
        $service->assignPool($agentId, 'missing');
        throw new RuntimeException('base-mode agent unexpectedly accepted a managed operation');
    } catch (InvalidArgumentException $error) {
        if ($error->getMessage() !== 'managed_authorization_required') {
            throw $error;
        }
    }

    $managementSecret = 'integration-management-secret';
    $service->authorizeManaged($agentId, $managementSecret, 600);
    $challengeResponse = signedControllerCall(
        $controller,
        $controllerIdentity,
        '/rbt-agent/v2/sync',
        $syncPayload($management),
        4,
        $agentId,
        $agentIdentity,
    );
    if (!isset($challengeResponse['management']['proof'])) {
        throw new RuntimeException('managed challenge was not delivered');
    }
    $management['authorized'] = true;
    signedControllerCall($controller, $controllerIdentity, '/rbt-agent/v2/sync', $syncPayload($management), 5, $agentId, $agentIdentity);

    $gatewayKey = base64_encode(str_repeat('g', 32));
    $service->savePool([
        'poolId' => 'pool-test',
        'title' => 'Integration pool',
        'tunnelPool' => '10.254.44.0/24',
        'overlayPool' => '10.220.0.0/16',
        'agentPrefixLength' => 24,
        'gatewayEndpoint' => 'rbt.example:51820',
        'gatewayPublicKey' => $gatewayKey,
        'gatewayTunnelAddress' => '10.254.44.1',
        'gatewayInterface' => 'wg-test0',
        'overlayType' => 'wireguard',
        'persistentKeepaliveSec' => 25,
        'allowedSourcePrefixes' => ['10.0.0.0/8'],
        'parameters' => [],
        'enabled' => true,
    ]);
    try {
        $service->savePool([
            'poolId' => 'pool-overlap-test',
            'title' => 'Overlapping pool',
            'tunnelPool' => '10.254.44.128/25',
            'overlayPool' => '10.221.0.0/16',
            'agentPrefixLength' => 24,
            'gatewayEndpoint' => 'rbt.example:51821',
            'gatewayPublicKey' => $gatewayKey,
            'gatewayTunnelAddress' => '10.254.44.129',
            'gatewayInterface' => 'wg-test1',
            'overlayType' => 'wireguard',
            'persistentKeepaliveSec' => 25,
            'allowedSourcePrefixes' => ['10.0.0.0/8'],
            'parameters' => [],
            'enabled' => true,
        ]);
        throw new RuntimeException('overlapping controller network pool was unexpectedly accepted');
    } catch (InvalidArgumentException $error) {
        if ($error->getMessage() !== 'network_pool_overlaps_existing_pool') {
            throw $error;
        }
    }
    $service->assignPool($agentId, 'pool-test');
    if ($db->query("SELECT desired_state->'overlay'->'parameters' FROM edge_agents WHERE agent_id = 'agent-integration-test'")->fetchColumn() !== '{}') {
        throw new RuntimeException('empty WireGuard parameters were not serialized as a JSON object');
    }
    $agent = $service->saveMapping($agentId, [
        'mappingId' => 'map-camera',
        'localIp' => '192.168.1.20',
        'comment' => 'all protocols and ports',
        'enabled' => true,
    ]);
    $desiredGeneration = (int) $agent['desired_generation'];
    $desired = signedControllerCall(
        $controller,
        $controllerIdentity,
        '/rbt-agent/v2/sync',
        $syncPayload($management),
        6,
        $agentId,
        $agentIdentity,
    );
    if ((int) $desired['desiredGeneration'] !== $desiredGeneration || count($desired['desiredState']['mappings'] ?? []) !== 1) {
        throw new RuntimeException('desired overlay state was not delivered');
    }

    $gatewayRunner = new IntegrationGatewayRunner($gatewayKey);
    $gatewayKeys = new GatewayKeyStore($root . '/gateway/keys', $gatewayRunner, $root . '/gateway/public');
    $gateway = new GatewayReconciler($db, $log, $root . '/gateway/state', $gatewayRunner, $gatewayKeys);
    $gatewayResult = $gateway->reconcileAll(false);
    if (!$gatewayResult['ok'] || ($gatewayResult['pools'][0]['state'] ?? null) !== 'ready') {
        throw new RuntimeException('gateway reconciliation did not complete: ' . json_encode($gatewayResult));
    }
    $syncCall = null;
    $nftCall = null;
    foreach ($gatewayRunner->calls as $call) {
        if ($call['command'] === 'wg' && ($call['arguments'][0] ?? null) === 'syncconf') {
            $syncCall = $call;
        }
        if ($call['command'] === 'nft' && $call['arguments'] === ['-f', '-']) {
            $nftCall = $call;
        }
    }
    if (!is_array($syncCall) ||
        !str_contains((string) $syncCall['config'], 'AllowedIPs = 10.254.44.2/32, 10.220.0.0/24')) {
        throw new RuntimeException('gateway WireGuard peer config is incomplete');
    }
    if (!is_array($nftCall) ||
        !str_contains($nftCall['stdin'], 'ip saddr {') ||
        !str_contains($nftCall['stdin'], '10.0.0.0/8') ||
        !str_contains($nftCall['stdin'], '10.254.44.1/32') ||
        !str_contains($nftCall['stdin'], 'oifname "wg-test0" drop')) {
        throw new RuntimeException('gateway firewall was not applied as one scoped nft batch');
    }
    if ($db->query("SELECT gateway_state FROM edge_overlay_leases WHERE agent_id = 'agent-integration-test'")->fetchColumn() !== 'ready') {
        throw new RuntimeException('gateway ready state was not persisted');
    }

    $action = $service->queueAction($agentId, 'diagnostics', [], 600);
    $withAction = signedControllerCall(
        $controller,
        $controllerIdentity,
        '/rbt-agent/v2/sync',
        $syncPayload($management, $desiredGeneration, 0),
        7,
        $agentId,
        $agentIdentity,
    );
    if (($withAction['actions'][0]['actionId'] ?? null) !== $action['action_id']) {
        throw new RuntimeException('queued action was not delivered');
    }
    signedControllerCall(
        $controller,
        $controllerIdentity,
        '/rbt-agent/v2/actions/' . $action['action_id'] . '/result',
        [
            'schemaVersion' => 1,
            'agentId' => $agentId,
            'actionId' => $action['action_id'],
            'idempotencyKey' => $withAction['actions'][0]['idempotencyKey'],
            'state' => 'completed',
            'result' => ['ok' => true],
            'error' => '',
        ],
        8,
        $agentId,
        $agentIdentity,
    );

    $revoking = $service->revokeAgent($agentId);
    if (($revoking['status'] ?? null) !== 'revoking') {
        throw new RuntimeException('admin revoke did not enter two-phase revoking state');
    }
    $revokeInstruction = signedControllerCall(
        $controller,
        $controllerIdentity,
        '/rbt-agent/v2/sync',
        $syncPayload($management, $desiredGeneration, 0),
        9,
        $agentId,
        $agentIdentity,
    );
    if (($revokeInstruction['revoke'] ?? false) !== true) {
        throw new RuntimeException('agent did not receive signed revoke instruction');
    }
    signedControllerCall($controller, $controllerIdentity, '/rbt-agent/v2/revoke', [
        'schemaVersion' => 1,
        'agentId' => $agentId,
    ], 10, $agentId, $agentIdentity);
    if ($db->query("SELECT pairing_status FROM edge_agents WHERE agent_id = 'agent-integration-test'")->fetchColumn() !== 'revoked') {
        throw new RuntimeException('agent revoke acknowledgement was not persisted');
    }
    $service->deleteMapping($agentId, 'map-camera');
    $service->releasePool($agentId);
    if ((int) $db->query("SELECT count(*) FROM edge_overlay_leases WHERE agent_id = 'agent-integration-test'")->fetchColumn() !== 0) {
        throw new RuntimeException('revoked agent overlay lease could not be released');
    }
} finally {
    try {
        $db->exec('SET search_path TO public');
        $db->exec('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
    } catch (Throwable) {
    }
    if (is_dir($root)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}

fwrite(STDOUT, "controller_postgres_integration_test: ok\n");

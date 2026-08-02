<?php

declare(strict_types=1);

use SesameWare\RbtAgent\GatewayReconciler;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/IPv4.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayReconciler.php';

$config = GatewayReconciler::renderInterfaceConfig([
    'listenPort' => 51820,
    'parameters' => ['jc' => 4, 'h1' => 12345],
    'peers' => [[
        'publicKey' => 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        'allowedIPs' => ['10.254.0.2/32', '10.220.17.0/24'],
    ]],
], 'BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=');

$expected = <<<'CONF'
[Interface]
PrivateKey = BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB=
ListenPort = 51820
H1 = 12345
Jc = 4

[Peer]
PublicKey = AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
AllowedIPs = 10.254.0.2/32, 10.220.17.0/24
CONF;

if (trim($config) !== trim($expected)) {
    fwrite(STDERR, "gateway config mismatch\n$config\n");
    exit(1);
}

fwrite(STDOUT, "gateway_config_test: ok\n");

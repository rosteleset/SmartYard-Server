#!/usr/bin/env php
<?php

declare(strict_types=1);

use SesameWare\RbtAgent\GatewayCommandRunner;
use SesameWare\RbtAgent\GatewayKeyStore;

require_once dirname(__DIR__) . '/bootstrap.php';

$interface = $argv[1] ?? 'wg-rbt';
$tool = $argv[2] ?? 'wg';
if (!in_array($tool, ['wg', 'awg'], true)) {
    fwrite(STDERR, "tool must be wg or awg\n");
    exit(2);
}
$runner = new GatewayCommandRunner();
$store = new GatewayKeyStore(
    rbtAgentGatewayStatePath() . '/keys',
    $runner,
    rbtAgentGatewayPublicKeyPath(),
);
$key = $store->ensure($interface, $tool);
fwrite(STDOUT, json_encode([
    'interface' => $interface,
    'tool' => $tool,
    'publicKey' => $key['publicKey'],
    'privateKeyPath' => $key['privateKeyPath'],
    'created' => $key['created'],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");

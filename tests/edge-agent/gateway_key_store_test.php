<?php

declare(strict_types=1);

use SesameWare\RbtAgent\GatewayCommandRunnerInterface;
use SesameWare\RbtAgent\GatewayKeyStore;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayCommandRunner.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/GatewayKeyStore.php';

final class KeyTestRunner implements GatewayCommandRunnerInterface
{
    public function find(string $command): ?string
    {
        return '/usr/bin/' . $command;
    }

    public function run(string $command, array $arguments = [], string $stdin = ''): string
    {
        if ($arguments === ['genkey']) {
            return base64_encode(str_repeat('p', 32)) . "\n";
        }
        if ($arguments === ['pubkey']) {
            return base64_encode(str_repeat('u', 32)) . "\n";
        }
        throw new RuntimeException('unexpected key runner call');
    }
}

$root = sys_get_temp_dir() . '/rbt-gateway-key-test-' . bin2hex(random_bytes(5));
$private = $root . '/private';
$public = $root . '/public';
$store = new GatewayKeyStore($private, new KeyTestRunner(), $public);

try {
    $key = $store->ensure('wg-test0');
    if (!$key['created'] || !is_file($private . '/wg-test0.key')) {
        throw new RuntimeException('private gateway key was not created');
    }
    if (!is_file($public . '/wg-test0.pub') || is_file($private . '/wg-test0.pub')) {
        throw new RuntimeException('public gateway key was not isolated from the private directory');
    }
    if ($store->publicKey('wg-test0') !== base64_encode(str_repeat('u', 32))) {
        throw new RuntimeException('public gateway key mismatch');
    }
    if ((fileperms($private . '/wg-test0.key') & 0777) !== 0600) {
        throw new RuntimeException('private gateway key permissions are not 0600');
    }
    if ((fileperms($public . '/wg-test0.pub') & 0777) !== 0644) {
        throw new RuntimeException('public gateway key permissions are not 0644');
    }
} finally {
    @unlink($private . '/wg-test0.key');
    @unlink($public . '/wg-test0.pub');
    @rmdir($private);
    @rmdir($public);
    @rmdir($root);
}

fwrite(STDOUT, "gateway_key_store_test: ok\n");

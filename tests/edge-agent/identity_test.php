<?php

declare(strict_types=1);

use SesameWare\RbtAgent\ControllerIdentity;
use SesameWare\RbtAgent\Protocol;

require_once __DIR__ . '/../../server/edge-agent-controller/lib/Protocol.php';
require_once __DIR__ . '/../../server/edge-agent-controller/lib/ControllerIdentity.php';

$directory = sys_get_temp_dir() . '/rbt-agent-identity-' . bin2hex(random_bytes(8));
$path = $directory . '/identity.json';
$first = ControllerIdentity::loadOrCreate($path, 'controller-test');
$second = ControllerIdentity::loadOrCreate($path);
if ($first->publicData() !== $second->publicData()) {
    throw new RuntimeException('controller identity changed across reload');
}
if ($first->id() !== 'controller-test' || $first->keyId() !== Protocol::keyId($first->publicKey())) {
    throw new RuntimeException('controller identity fields are inconsistent');
}
if ((fileperms($path) & 0777) !== 0600) {
    throw new RuntimeException('controller identity mode is not 0600');
}
@unlink($path);
@rmdir($directory);
fwrite(STDOUT, "identity_test: ok\n");

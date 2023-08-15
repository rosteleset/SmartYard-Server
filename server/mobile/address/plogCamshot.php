<?php

use logger\Logger;

$logger = Logger::channel('address-plog');

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$bytes = $files->getFileBytes($uuid);

$logger->debug('plogCamshot()', ['uuid' => $uuid, 'bytes' => count($bytes)]);

if ($bytes) {
    header("Content-Type: image/jpeg");

    echo $bytes;

    exit;
}

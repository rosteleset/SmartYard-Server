<?php

use logger\Logger;

$logger = Logger::channel('plog', 'camshot');

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);

$logger->debug('plogCamshot()', ['uuid' => $uuid]);

try {
    $bytes = $files->getFileBytes($uuid);

    $logger->debug('plogCamshot()', ['uuid' => $uuid, 'bytes' => strlen($bytes)]);

    header('Content-Type: image/jpeg');

    echo $bytes;

    exit;
} catch (Exception $e) {
    $logger->error($e);
}

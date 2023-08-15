<?php

use logger\Logger;

$logger = Logger::channel('address-plog');

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);

$logger->debug('plogCamshot()', ['uuid' => $uuid]);

try {
    $bytes = $files->getFileBytes($uuid);

    $logger->debug('plogCamshot()', ['bytes' => $bytes ? strlen($bytes) : -1]);

    if ($bytes) {
        $bytes->debug('plogCamshot()', ['uuid' => $uuid, 'bytes' => count($bytes)]);

        header('Content-Type: image/jpeg');

        echo $bytes;

        exit;
    }
} catch (Exception $e) {
    $logger->error('Error get plogCamshot()' . PHP_EOL . $e);
}

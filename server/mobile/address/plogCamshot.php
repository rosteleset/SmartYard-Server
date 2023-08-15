<?php

use logger\Logger;

$logger = Logger::channel('address-plog');

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);

$logger->debug('plogCamshot()', ['uuid' => $uuid]);

try {
    $bytes = $files->getFileBytes($uuid);

    if ($bytes) {
        $logger->debug('plogCamshot()', ['bytes' => strlen($bytes)]);

        header('Content-Type: image/jpeg');

        echo unpack("C*", $bytes);

        exit;
    }
} catch (Exception $e) {
    $logger->error('Error get plogCamshot()' . PHP_EOL . $e);
}

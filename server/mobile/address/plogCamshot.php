<?php

use logger\Logger;

$logger = Logger::channel('address-plog');

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);

$logger->debug('plogCamshot()', ['uuid' => $uuid]);

try {
    $file = $files->getFile($uuid);

    if ($file) {
        $logger->debug('plogCamshot()', ['uuid' => $uuid, 'fileInfo' => $file['fileInfo']]);

        $contents = stream_get_contents($file['stream']);

        if ($contents) {
            $metaData = $file['fileInfo']['metadata'];

            header('Content-Type: ' . isset($metaData['contentType']) ? $metaData['contentType'] : 'image/jpeg');

            echo $contents;

            exit;
        }
    }
} catch (Exception $e) {
    $logger->error('Error get plogCamshot()' . PHP_EOL . $e);
}

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
            $image = imagecreatefromstring(base64_decode($contents));

            if ($image) {
                header('Content-Type: image/jpeg');

                imagejpeg($image);
                imagedestroy($image);

                exit;
            }
        }
    }
} catch (Exception $e) {
    $logger->error('Error get plogCamshot()' . PHP_EOL . $e);
}

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

        $data = stream_get_contents($file['stream']);

        echo '<img src="data:image/jpeg;base64,'.$data.'">';

        // $image = imagecreatefromstring(stream_get_contents($file['stream']));

        // if ($image) {
        //     header('Content-Type: image/jpeg');

        //     imagejpeg($image);
        //     imagedestroy($image);

        //     exit;
        // }
    }
} catch (Exception $e) {
    $logger->error('Error get plogCamshot()' . PHP_EOL . $e);
}

<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFile($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'info' => $img['fileInfo']]);

if ($img) {
    header(("Content-Type", "image/jpeg"));

    echo stream_get_contents($img['stream']);

    exit;
}

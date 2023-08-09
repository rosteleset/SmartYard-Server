<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFile($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'img' => $img["fileInfo"]]);

if ($img) {
    $contents = stream_get_contents($img['stream']);

    if ($contents) {
        echo $contents;
    }
}

<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFileContent($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid]);

if ($img) {
    echo $img;

    exit;
}

<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFileContent($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'count' => is_countable($img) ? count($img) : -1]);

if ($img) {
    echo $img;

    exit;
}

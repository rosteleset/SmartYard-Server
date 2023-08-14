<?php

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$bytes = $files->getFileBytes($uuid);

if ($bytes) {
    header("Content-Type: image/jpeg");

    echo $bytes;

    exit;
}

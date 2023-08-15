<?php
$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$file = $files->getFile($uuid);

if ($file) {
    $metaData = $file['fileInfo']->metadata;

    header('Content-Type: ' . isset($metaData->contentType) ? $metaData->contentType : 'image/jpeg');

    echo stream_get_contents($file['steam']);

    exit;
}

<?php

use logger\Logger;

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFile($uuid);

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'img' => $img]);

if ($img) {
    $content_type = "image/jpeg";
    $meta_data = $files->getFileMetadata($uuid);

    Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'meta' => $meta_data]);

    if (isset($meta_data->contentType))
        $content_type = $meta_data->contentType;

    Logger::channel('plog')->debug('plogCamshot', ['uuid' => $uuid, 'data' => stream_get_contents($img['stream'])]);

    $image = imagecreatefromstring(stream_get_contents($img['stream']));

    if ($image) {
        Logger::channel('plog')->debug('plogCamshot send', ['uuid' => $uuid]);

        header("Content-Type: $content_type");

        if (!imagejpeg($image))
            Logger::channel('plog')->debug('plogCamshot not send', ['uuid' => $uuid]);

        imagedestroy($image);

        exit;
    } else Logger::channel('plog')->debug('plogCamshot not create image', ['uuid' => $uuid]);
}

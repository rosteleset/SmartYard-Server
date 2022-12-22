<?php
    $files = loadBackend('files');
    $uuid = $files->fromGUIDv4($param);
    $img = $files->getFile($uuid);
    if ($img) {
        $content_type = "image/jpeg";
        $meta_data = $files->getFileMetadata($uuid);
        if (isset($meta_data->contentType)) {
            $content_type = $meta_data->contentType;
        }
        header("Content-Type: $content_type");
        echo(stream_get_contents($img['stream']));
        exit;
    }

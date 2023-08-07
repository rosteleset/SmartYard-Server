<?php

use logger\Logger;

Logger::channel('plog')->debug('plogCamshot', ['uuid' => $param]);

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);

$file = $files->getBytes($uuid);

if ($file) {
    header("Content-Type: image/jpeg");

    echo $file;
}
// $img = $files->getFile($uuid);

// if ($img) {
//     $content_type = "image/jpeg";
//     $meta_data = $files->getFileMetadata($uuid);

//     if (isset($meta_data->contentType)) {
//         $content_type = $meta_data->contentType;
//     }

//     header("Content-Type: $content_type");

//     echo (stream_get_contents($img['stream']));

//     exit;
// }

<?php

$files = loadBackend('files');
$uuid = $files->fromGUIDv4($param);
$img = $files->getFile($uuid);

if ($img) {
    $image = imagecreatefromstring(stream_get_contents($img['stream']));

    if ($image) {
        header("Content-Type: image/jpeg");

        imagejpeg($image);
        imagedestroy($image);

        exit;
    }
}

<?php
    $plog = loadBackend('plog');
    $img = $plog->getEventImage($param);
    if ($img) {
        $content_type = "image/jpeg";
        $meta_data = json_decode(MongoDB\BSON\toJSON(MongoDB\BSON\fromPHP($img['fileInfo']['metadata'])));
        if (isset($meta_data->contentType)) {
            $content_type = $meta_data->contentType;
        }
        header("Content-Type: $content_type");
        echo(stream_get_contents($img['stream']));
        exit;
    }

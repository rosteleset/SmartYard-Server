<?php
    $plog = loadBackend('plog');
    $img = $plog->getEventImage($param);
    if ($img) {
        $content_type = "image/jpeg";
        $q = json_decode(MongoDB\BSON\toJSON(MongoDB\BSON\fromPHP($img['meta'])));
        foreach ($q->metadata as $item) {
            if ($item->contentType) {
                $content_type = $item->contentType;
                break;
            }
        }
        header("Content-Type: $content_type");
        echo $img['contents'];
    }

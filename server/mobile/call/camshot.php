<?php

    $hash = $param;

    $memfs = loadBackend("memfs");

    if ($memfs) {
        $img = $memfs->getFile($hash);
    } else {
        $img = $redis->get("shot_" . $hash);
    }


    if ($img) {
        header('Content-Type: image/jpeg');
        echo $img;
        exit;
    }
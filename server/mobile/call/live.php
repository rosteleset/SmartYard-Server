<?php

    $hash = $param;

    $cameraData = @$redis->get("live_" . $hash);

    if ($cameraData === false) {
        $memfs = loadBackend("memfs");

        if ($memfs) {
            $cameraData = $memfs->getFile($hash);
            if ($cameraData) {
                header('Content-Type: image/jpeg');
                echo $cameraData;
                exit;
            }
        } else {
            response(404);
        }
    }

    $cameraId = is_numeric($cameraData) ? $cameraData : null;

    if ($cameraId === null) {
        header('Content-Type: image/jpeg');
        echo $cameraData;
        exit;
    }

    $camerasBackend = loadBackend('cameras');

    if (!$camerasBackend) {
        response(500);
    }

    $camera = $camerasBackend->getCamera($cameraId);

    if (!$camera) {
        response(404);
    }

    $snapshot = $camerasBackend->getSnapshot($camera['cameraId']);

    if ($snapshot === null) {
        response(503);
    }

    header('Content-Type: image/jpeg');
    echo $snapshot;

    exit;

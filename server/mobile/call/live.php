<?php

    $hash = $param;

    file_put_contents("/tmp/test_live", $hash);

    $json_camera = @$redis->get("live_" . $hash);
    $camera_params = @json_decode($json_camera, true);

    $camera = @loadCamera($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

    if (!$camera) {
        response(404);
    }

    header('Content-Type: image/jpeg');
    echo $camera->camshot();

    exit;

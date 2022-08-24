<?php

    $hash = $param;

    $json_camera = @$redis->get("live_" . $hash);
    $camera_params = @json_decode($json_camera, true);

    file_put_contents("/tmp/test_live", print_r($camera_params, true));

    $camera = @loadCamera($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

    if (!$camera) {
        response(404);
    }

    header('Content-Type: image/jpeg');
    echo $camera->camshot();

    exit;

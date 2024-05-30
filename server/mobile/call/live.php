<?php

$hash = $param;

$json_camera = @$redis->get("live_" . $hash);
$camera_params = @json_decode($json_camera, true);

try {
    $camera = loadDevice('camera', $camera_params["model"], $camera_params["url"], $camera_params["credentials"]);
    header('Content-Type: image/jpeg');
    echo $camera->getCamshot();
} catch (Exception $e) {
    error_log("Error getting live snapshot ({$camera_params['url']}): " . $e->getMessage());
    response(404);
}

exit;

<?php

use Selpol\Service\RedisService;

$hash = $param;

$json_camera = container(RedisService::class)->getRedis()->get("live_" . $hash);
$camera_params = @json_decode($json_camera, true);

$camera = @camera($camera_params["model"], $camera_params["url"], $camera_params["credentials"]);

if (!$camera)
    response(404);

header('Content-Type: image/jpeg');

echo $camera->camshot();

exit;
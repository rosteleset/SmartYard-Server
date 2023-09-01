<?php

use Selpol\Service\RedisService;

$hash = $param;

$img = container(RedisService::class)->getRedis()->get("shot_" . $hash);

if ($img) {
    header('Content-Type: image/jpeg');

    echo $img;

    exit;
}
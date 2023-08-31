<?php

$hash = $param;

$img = @$redis->get("shot_" . $hash);

if ($img) {
    header('Content-Type: image/jpeg');

    echo $img;

    exit;
}
<?php

require_once __DIR__ . '/../../utils/checkint.php';

$camera_id = $param;
if (!isset($camera_id) || $camera_id === 0)
    response(404);

$cameras = loadBackend("cameras");
if (!$cameras)
    response(404);

$cam = $cameras->getCamera($camera_id);
if (!$cam)
    response(404);

$model = loadCamera($cam["model"], $cam["url"], $cam["credentials"]);
if (!$model)
    response(404);

header('Content-Type: image/jpeg');
echo $model->camshot();

exit;

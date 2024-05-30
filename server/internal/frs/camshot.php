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

try {
    $model = loadDevice('camera', $cam["model"], $cam["url"], $cam["credentials"]);
    header('Content-Type: image/jpeg');
    echo $model->getCamshot();
} catch (Exception $e) {
    error_log("Error getting snapshot ($camera_id): " . $e->getMessage());
    response(404);
}

exit;

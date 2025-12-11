<?php

$camera_id = $param;
if (!isset($camera_id) || $camera_id === 0) {
    response(400);
}

$camerasBackend = loadBackend('cameras');
if (!$camerasBackend) {
    response(500);
}

$camera = $camerasBackend->getCamera($camera_id);
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

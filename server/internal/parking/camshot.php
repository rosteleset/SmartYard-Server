<?php

// current frame of a camera for the parking occupancy service, JPEG

$camera_id = $param ?? null;

if (!checkInt($camera_id) || $camera_id <= 0) {
    response(400);
}

$cameras = loadBackend("cameras");

if (!$cameras) {
    response(500);
}

$camera = $cameras->getCamera((int)$camera_id);

if (!$camera) {
    response(404);
}

$snapshot = $cameras->getSnapshot($camera["cameraId"]);

if ($snapshot === null) {
    response(503);
}

header("Content-Type: image/jpeg");
echo $snapshot;

exit;

<?php

// current frame of a camera for the parking occupancy service: JPEG, PNG, BMP or WebP with its type

$camera_id = $param ?? null;

if (!checkInt($camera_id) || $camera_id <= 0) {
    response(400);
}

$cameras = loadBackend("cameras");
$parking = loadBackend("parking");

if (!$cameras || !$parking) {
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

$type = $parking->frameType($snapshot);

// the camera answered with something that is not a frame the parking can work with
if (!$type) {
    response(502);
}

header("Content-Type: " . $type["mime"]);
echo $snapshot;

exit;

<?php

// occupancy result of a processed frame from the parking occupancy service:
// { cameraId, revision, frameWidth, frameHeight, freeSpaces: [ { parkingAreaId, spaces: [ [ { x, y } x 4 ] ] } ], frame: base64 JPEG }
// 200 - stored, 204 - discarded (computed with an outdated revision), 400 - invalid result

$parking = loadBackend("parking");

if (!$parking) {
    response(404);
}

$camera_id = $postdata["cameraId"] ?? null;
$frame = is_string($postdata["frame"] ?? null) ? base64_decode($postdata["frame"], true) : false;

if (!checkInt($camera_id) || $camera_id <= 0 || $frame === false) {
    response(400);
}

$status = $parking->saveResult((int)$camera_id, $postdata, $frame);

if ($status === false) {
    response($parking->lastFailure() == "parkingInvalidResult" ? 400 : 500);
}

response($status == "accepted" ? 200 : 204);

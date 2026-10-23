<?php

// failed processing attempt from the parking occupancy service: { cameraId, revision, status, reason }
// status: cameraUnavailable, detectionFailed or stale; 200 - stored, 400 - invalid

$parking = loadBackend("parking");

if (!$parking) {
    response(404);
}

$camera_id = $postdata["cameraId"] ?? null;

if (!checkInt($camera_id) || $camera_id <= 0) {
    response(400);
}

if (!$parking->saveStatus((int)$camera_id, $postdata["status"] ?? null, (string)($postdata["reason"] ?? ""), $postdata["revision"] ?? null)) {
    response($parking->lastFailure() == "parkingInvalidStatus" ? 400 : 500);
}

response(200);

<?php

// snapshot of the current parking configuration revision of a camera with { revision, ready }

$camera_id = $param ?? null;

if (!checkInt($camera_id) || $camera_id <= 0) {
    response(400);
}

$parking = loadBackend("parking");

if (!$parking) {
    response(404);
}

$revision = $parking->currentRevision((int)$camera_id);

if ($revision === false) {
    response(500);
}

if ($revision === null) {
    response(404);
}

response(200, $revision);

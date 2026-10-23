<?php

// cameras the parking occupancy service has to process: [ { cameraId, revision, interval } ]

$parking = loadBackend("parking");

if (!$parking) {
    response(404);
}

$cameras = $parking->camerasToProcess();

if ($cameras === false) {
    response(500);
}

response(200, $cameras);

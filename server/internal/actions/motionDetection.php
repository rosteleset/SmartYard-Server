<?php
    /** Get ip motion detection device, find stream_id and frs url.
     * Send POST request to FRS.
     */
    [
        "date" => $date,
        "ip" => $ip,
        "motionActive" => $motionActive
    ] = $postdata;

    $query = 'SELECT camera_id, frs FROM cameras WHERE ip = :ip';
    $params = ["ip" => $ip];

    [0 => [
        "camera_id" => $streamId,
        "frs" => $frsUrl
    ]] = $db->get($query, $params, []);

    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

    if (isset($frsUrl, $streamId)) {
        $apiResponse = apiExec($frsUrl . "/api/motionDetection", $payload);
        response(201, $apiResponse);
    }

    response(200);
    exit();
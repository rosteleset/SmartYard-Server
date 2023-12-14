<?php
    /**
     * Get motion detection device IP, find stream_id, and FRS URL.
     * Send POST request to FRS.
     */

    $requiredKeys = ["date", "motionActive", "ip", "subId"];

    // TODO: make validate payload utils. Use optional params
    foreach ($requiredKeys as $key) {
        if (!isset($postdata[$key])){
            response(406, false, false, "Invalid payload: missing '$key'");
            exit();
        }
    }

    [
        "date" => $date,
        "ip" => $ip,
        "subId" => $subId,
        "motionActive" => $motionActive,
    ] = $postdata;

    // Validate IP or subId presence
    if (!($ip || $subId)) {
        response(406, false, false, "Invalid payload: not valid 'ip' or 'subId'");
        exit();
    }

    $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND (ip = :ip OR sub_id = :sub_id)';
    $params = ["ip" => $ip, "sub_id" => $subId, "frs" => "-"];
    $result = $db->get($query, $params);

    if (!$result) {
        response(200, false, false, "FRS not enabled on this stream");
        exit();
    }

    // Extract streamId and frsUrl using an associative array destructuring
    ["camera_id" => $streamId, "frs" => $frsUrl] = $result[0];

    // prepare payload for API request
    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

    $apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
    response(201, $apiResponse);

    exit();
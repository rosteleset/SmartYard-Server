<?php
    /** Get an ip motion detection device, find stream_id and frs url.
     * Send POST request to FRS.
     */
    if (!isset($postdata["date"], $postdata["motionActive"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    if (!isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "subId" => $subId,
        "motionActive" => $motionActive,
    ] = $postdata;

    $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND (ip = :ip OR sub_id = :sub_id)';
    $params = ["ip" => $ip, "sub_id" => $subId, "frs" => "-"];
    $result = $db->get($query, $params);

    if (!$result) {
        response(202, false, false, "FRS not enabled on this stream");
        exit();
    }

    ["camera_id" => $streamId, "frs" => $frsUrl] = $result[0];

    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

    $apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
    response(201, $apiResponse);

    exit();
<?php
    /** Get ip motion detection device, find stream_id and frs url.
     * Send POST request to FRS.
     */
    if (!isset(
        $postdata["date"],
        $postdata["ip"],
        $postdata["motionActive"]
    )) {
        response(406, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "motionActive" => $motionActive
    ] = $postdata;

    $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND ip = :ip';
    $params = ["ip" => $ip, "frs" => "-"];
    $result = $db->get($query, $params, []);

    if ( !$result ) {
        response(200, "FRS not enabled on this stream");
        exit();
    }

    [0 => [
        "camera_id" => $streamId,
        "frs" => $frsUrl
    ]] = $result;

    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

   $apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
   response(201, $apiResponse);

    exit();
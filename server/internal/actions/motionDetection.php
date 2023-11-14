<?php
    /**
     * @api {post} /internal/actions/motionDetection Send POST request to FRS for Motion Detection
     * @apiVersion 1.0.0
     * @apiDescription Get IP motion detection device, find 'stream_id' and FRS URL. Send POST request to FRS for Motion Detection.
     *
     * @apiGroup Motion Detection
     *
     * @apiParam {string} date Date of the motion detection event.
     * @apiParam {string} ip IP address of the motion detection device.
     * @apiParam {boolean} motionActive Indicates whether motion is active (true) or not (false).
     *
     * @apiSuccessExample Success Response
     * HTTP/1.1 201 Created
     * {
     *     "message": "Motion Detection request sent successfully"
     * }
     *
     * @apiErrorExample Error Response
     * HTTP/1.1 406 Not Acceptable
     * {
     *     "error": "Invalid payload",
     *     "message": "Please provide valid payload parameters"
     * }
     * HTTP/1.1 404 Not Found
     * {
     *     "message": "FRS not enabled on this stream",
     * }
     */
    if (!isset($postdata["date"], $postdata["ip"], $postdata["motionActive"])) {
        response(406, null, null, "Please provide valid payload parameters");
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

    if (!$result) {
        response(404, null, null, "FRS not enabled on this stream");
        exit(1);
    }

    [0 => [
        "camera_id" => $streamId,
        "frs" => $frsUrl
    ]] = $result;

    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

    $apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
    response(201, null, null, "Motion Detection request sent successfully");

    exit(1);
<?php

    /**
     * @api {post} /actions/motionDetection motion detection event processing
     * @apiVersion 1.0.0
     * @apiDescription Find stream_id and frs url and call to FRS.
     *
     * @apiGroup internal
     *
     * @apiParam {Object}
     * @apiParam {Number} date timestamp related to the call motion detection event.
     * @apiParam {string|null} ip IP address associated with the event.
     * @apiParam {string|null} subId subscription ID related to the event
     * @apiParam {string="t","f"} motionActive start or stop motion
     *
     * @apiSuccess {Number} status code indicating success
     * @apiSuccess {String} message success message indicating processing of events.
     *
     * @apiErrorExample {json} Error Responses:
     *      HTTP/1.1 406 Invalid payload
     *      HTTP/1.1 404 Not found
     */

    //TODO: add payload validator handler.
    if (!isset($postdata["date"], $postdata["motionActive"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    if (!isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    ["date" => $date, "ip" => $ip, "subId" => $subId, "motionActive" => $motionActive] = $postdata;

    $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND (ip = :ip OR sub_id = :sub_id)';
    $params = ["ip" => $ip, "sub_id" => $subId, "frs" => "-"];
    $result = $db->get($query, $params);

    if (!$result) {
        response(200, false, false, "FRS not enabled on this stream");
        exit();
    }

    ["camera_id" => $streamId, "frs" => $frsUrl] = $result[0];

    $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

    $apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
    response(204);

    exit();
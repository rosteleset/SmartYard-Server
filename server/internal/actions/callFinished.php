<?php

    /**
     * @api {post} /internal/actions/callFinished Store Call Done Event
     * @apiVersion 1.0.0
     * @apiDescription Store events to the database (plog_call_done).
     *
     * @apiGroup Call Events
     *
     * @apiParam {string} date Date of the call event.
     * @apiParam {string} ip IP address related to the call event.
     * @apiParam {string} callId Unique identifier for the call event.
     *
     * @apiSuccessExample Success Response
     * HTTP/1.1 201 Created
     * {
     *     "message": "Call Done event stored successfully",
     *     "data": {
     *         "id": "123456"
     *     }
     * }
     *
     * @apiErrorExample Error Response
     * HTTP/1.1 406 Not Acceptable
     * {
     *     "name": "Not Acceptable",
     *     "message": "Please provide valid payload parameters"
     * }
     */

    if (!isset(
        $postdata["date"],
        $postdata["ip"],
    )) {
        response(406, null, null, "Please provide valid payload parameters");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "callId" => $callId
    ] = $postdata;

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $callId);

    response(201, ["id" => $callDone], null, "Call Done event stored successfully");
    exit();
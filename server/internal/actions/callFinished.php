<?php

    /**
     * @api {post} /actions/callFinished store events to db 'plog_call_done'
     * @apiVersion 1.0.0
     * @apiDescription
     *
     * @apiGroup internal
     *
     * @apiParam {Object}
     * @apiParam {string} date timestamp related to the call finished event.
     * @apiParam {string|null} ip IP address associated with the event.
     * @apiParam {string|null} subId subscription ID related to the event
     * @apiParam callId ID of the call related to the event.
     *
     * @apiSuccess {Number} status code indicating success
     *
     * @apiErrorExample {json} Error Responses:
     *      HTTP/1.1 406 Invalid payload
     *      HTTP/1.1 404 Not found
     */

    if (!isset($postdata["date"]) || !isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    ["date" => $date, "ip" => $ip, "subId" => $subId, "callId" => $callId] = $postdata;

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $subId, $callId);

    response(204);
    exit();

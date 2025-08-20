<?php

    /**
     * @api {post} /actions/openDoor Store events to db 'plog_door_open', call "Freeze motion detection" to FRS
     * @apiVersion 1.0.0
     * @apiDescription *** in process ****
     *
     * @apiGroup internal
     *
     * @apiParam {Number} date timestamp related to the call finished event.
     * @apiParam {string|null} ip IP address associated with the event.
     * @apiParam {string|null} subId subscription ID related to the event
     * @apiParam {Number=1,2,3,4,5,6,7,8} event Event code
     * @apiParam {Number=0,1} door Number door 0 - main door , 1 0 second door
     * @apiParam {string} detail
     *
     * @apiSuccess {Number} status code indicating success
     *
     * @apiErrorExample {json} Error Responses:
     *      HTTP/1.1 406 Invalid payload
     *      HTTP/1.1 404 Not found
     */


    /*
     * TODO:
     *      -   refactor events code, move to global constants?
     *      -   Define Events
     */

    $events = [
        "NOT_ANSWERED" => 1,
        "ANSWERED" => 2,
        "OPEN_BY_KEY" => 3,
        "OPEN_BY_APP" => 4,
        "OPEN_BY_FACE_ID" => 5,
        "OPEN_BY_CODE" => 6,
        "OPEN_BY_CALL" => 7,
        "OPEN_BY_BUTTON" => 8,
    ];

    if (!isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    if (!isset(
        $postdata["date"],
        $postdata["event"],
        $postdata["door"],
        $postdata["detail"],
    )) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "subId" => $subId,
        "event" => $event,
        "door" => $door,
        "detail" => $detail,
    ] = $postdata;

    $plog = loadBackend('plog');

    switch ($event) {
        case $events['OPEN_BY_KEY']:
            // Update last seen
            $households = loadBackend('households');
            $households->lastSeenKey($detail);

            // Add door open data
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $subId, $event, $door, $detail);

            // paranoidEvent (pushes)
            $households->paranoidEvent($ip, $subId, $door, "rfId", $detail);

            response(201, ["id" => $plogDoorOpen]);

        case $events['OPEN_BY_CODE']:
            // Add door open data
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $subId, $event, $door, $detail);

            // TODO: paranoidEvent (pushes)
            // $households->paranoidEvent($entranceId, "code", $details);

            response(201, ["id" => $plogDoorOpen]);

        case $events['OPEN_BY_CALL']:
            /* not used
            example event: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(204);

        case $events['OPEN_BY_BUTTON']:
            //FIXME: move SQL request to backend 'helpers'

            /* "Host-->FRS | Event: open door by button.
            send request to FRS for "freeze motion detection" on this entry"
            */
            $result = $db->get('SELECT frs, camera_id FROM cameras
                                WHERE camera_id = (
                                SELECT camera_id FROM houses_domophones
                                LEFT JOIN houses_entrances USING (house_domophone_id)
                                WHERE (ip = :ip OR sub_id = :sub_id) AND domophone_output = :door)',
                ["ip" => $ip, "sub_id" => $subId, "door" => $door]);

            if ($result) {
                ["camera_id" => $streamId, "frs" => $frsUrl] = $result[0];
                //FIXME: check frs field
                if (isset($frsUrl) && filter_var($frsUrl, FILTER_VALIDATE_URL)) {
                    $payload = ["streamId" => strval($streamId)];
                    $frsApiResponse = apiExec("POST", $frsUrl . "/api/doorIsOpen", $payload);
                    response(201, $frsApiResponse);
                }
                // TODO: error logging on debug level in config
                //error_log("Internal API, method openDoor: invalid frsUrl on camera_id: $streamId");
            }
            response(204);
    }

    exit();

<?php
    /*
     * Store events to db plog_door_open.
     * "freeze motion detection" request for SRS
    */

    /**
     * @api {post} /internal/actions/storeDoorEvent Store Door Event
     * @apiVersion 1.0.0
     * @apiDescription Store events related to 'door openings' in the database (plog_door_open).
     *
     * @apiGroup Door Events
     *
     * @apiParam {string} date Date of the door event.
     * @apiParam {string} ip IP address related to the door event.
     * @apiParam {string} event Type of the door event.
     * @apiParam {string} door Door identifier associated with the event.
     * @apiParam {string} detail Additional details about the door event.
     *
     * @apiSuccessExample Success Response
     * HTTP/1.1 201 Created
     * {
     *     "message": "Door event stored successfully",
     *     "data": {
     *         "id": "42"
     *     }
     * }
     *
     * @apiErrorExample Error Response
     * HTTP/1.1 404 Not Found
     * {
     *     "name": "Not Found",
     *     "message": "FRS not enabled on this stream
     * }
     *  HTTP/1.1 406 Not Acceptable
     *  {
     *      "name": "Not Acceptable",
     *      "message": "Please provide valid payload parameters"
     *  }
     */


/*
 * TODO: refactor events code ?!
 * Define Events
 */
    $events = [
        "NOT_ANSWERED" => 1,
        "ANSWERED" => 2,
        "OPEN_BY_KEY" => 3,
        "OPEN_Not AcceptableBY_APP" => 4,
        "OPEN_BY_FACE_ID" => 5,
        "OPEN_BY_CODE" => 6,
        "OPEN_BY_CALL" => 7,
        "OPEN_BY_BUTTON" => 8
    ];

    if (!isset(
        $postdata["date"],
        $postdata["ip"],
        $postdata["event"],
        $postdata["door"],
        $postdata["detail"],
      )) {
        response(406, null, null, "Please provide valid payload parameters");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "event" => $event,
        "door" => $door,
        "detail" => $detail
    ] = $postdata;

    $plog = loadBackend('plog');

    switch ($event) {
        case $events['OPEN_BY_KEY']:
        case $events['OPEN_BY_CODE']:
            //Store event to db
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door, $detail);
            response(201, ["id" => $plogDoorOpen], null, "Door event stored successfully");
            break;
        case $events['OPEN_BY_CALL']:
            /* not used
            example event: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(200);
            break;
        case $events['OPEN_BY_BUTTON']:
            /* "Host-->FRS | Event: open door by button.
            send request to FRS for "freeze motion detection" on this entry"
            */
            [0 => [
                "camera_id" => $streamId,
                "frs" => $frsUrl
            ]] = $db->get('SELECT frs, camera_id FROM cameras 
                        WHERE camera_id = (
                        SELECT camera_id FROM houses_domophones 
                        LEFT JOIN houses_entrances USING (house_domophone_id)
                        WHERE ip = :ip AND domophone_output = :door)',
                ["ip" => $ip, "door" => $door],
                []);

            if (isSet($frsUrl)){
                $payload = ["streamId" => strval($streamId)];
                $apiResponse = apiExec("POST", $frsUrl . "/api/doorIsOpen", $payload);
                response(204);
            }

            response(404, null, null, "FRS not enabled on this stream");
            break;
    }

    exit();

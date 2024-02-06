<?php
    /*
     * Store events to db plog_door_open.
     * "Freeze motion detection" request for SRS
    */

    /*
     * TODO: refactor events code, move to global constants?
     * Define Events
     */
    $events = [
        "NOT_ANSWERED" => 1,
        "ANSWERED" => 2,
        "OPEN_BY_KEY" => 3,
        "OPEN_BY_APP" => 4,
        "OPEN_BY_FACE_ID" => 5,
        "OPEN_BY_CODE" => 6,
        "OPEN_BY_CALL" => 7,
        "OPEN_BY_BUTTON" => 8
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
        case $events['OPEN_BY_CODE']:
            //Store event to db
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $subId, $event, $door, $detail);
            response(201, ["id" => $plogDoorOpen]);
            break;

        case $events['OPEN_BY_CALL']:
            /* not used
            example event: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(204);
            break;

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
            break;
    }
    exit();

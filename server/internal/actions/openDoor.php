<?php
    /*Store events to db plog_door_open.
     "freeze motion detection" request for SRS
    */
    if (!isset(
        $postdata["date"],
        $postdata["ip"],
        $postdata["event"],
        $postdata["door"],
        $postdata["detail"],
      )) {
        response(406, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "event" => $event,
        "door" => $door,
        "detail" => $detail
    ] = $postdata;

    if (!isset($date, $ip, $event, $door, $detail)) {
        response(406, "Invalid payload");
        exit();
    }

    //TODO: refactor events code ?!
    try {
        $events = @json_decode(file_get_contents(__DIR__ . "/../../syslog/utils/events.json"), true);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        response(555, [
            "error" => "events config is missing",
        ]);
    }
    $plog = loadBackend('plog');

    switch ($event) {
        case $events['OPEN_BY_KEY']:
        case $events['OPEN_BY_CODE']:
            //Store event to db
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door, $detail);
            response(201, ["id" => $plogDoorOpen]);
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
                response(201, $apiResponse);
            }

            response(200);
            break;
    }

    exit();

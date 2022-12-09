<?php
    //Наполняем событиями с  панели  домофона таблицу internal.plog_door_open
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

    //TODO:вынести куда-нибудь коды событий для последующего переиспользования.
    try {
        $events = @json_decode(file_get_contents(__DIR__ . "../../../syslog/utils/events.json"), true);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        response(555, [
            "error" => "events config is missing",
        ]);
    }
    $plog = loadBackend('plog');

    switch ($event) {
        case $events['OPEN_BY_KEY']:
        //Прочие действия предпологаемые для соьытия "открытие двери по коду"
        case $events['OPEN_BY_CODE']:
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door, $detail);
            response(201, ["id" => $plogDoorOpen]);
            break;

        case $events['OPEN_BY_CALL']:
            /* Пример события: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(200);
            break;
        case $events['OPEN_BY_BUTTON']:
            // "Host-->FRS | Уведомление об открытии двери"
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
                $apiResponse = apiExec($frsUrl . "/api/doorIsOpen", ["streamId" => strval($streamId)]);
                response(201,$apiResponse);
            }

            response(200);
            break;
    }

    exit();

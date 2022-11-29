<?php
    //Наполняем событиями с  панели  домофона таблицу internal.plog_door_open
    [
        "date" => $date,
        "ip" => $ip,
        "event" => $event,
        "door" => $door,
        "detail" => $detail, 
        "expire" => $expire
    ] = $postdata;

    if (!isset($date, $ip, $event, $door, $detail, $expire)) {
        response(406, "Invalid payload");
        exit();
    }

    //TODO:вынести кудани-будь коды событий для последующего переиспользования.
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
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door,$detail);
            response(201, ["id" => $plogDoorOpen]);
            break;

        case $event['OPEN_BY_CALL']:
            /*TODO:функционал белого кролика. Отккрытие двери по звонку из приложеня.
             * Только при условии что last_opened  в таблице houses_flats используется для "белого кролика"
             * Проверяем что это калитка, только в этом случае обновляем last_opened в таблице houses_flats.
             * Пример события: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(200);
            break;
    }

    exit();

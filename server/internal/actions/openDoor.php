<?php
    //Наполняем событиями с  панели  домофона в таблицу internal.plog_door_open
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

    try {
        $events = @json_decode(file_get_contents(__DIR__ . "../../../syslog/utils/events.json"), true);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        response(555, [
            "error" => "events config",
        ]);
    }

    switch ($event) {
        case $events['OPEN_BY_KEY']:

        case $events['OPEN_BY_CODE']:
            //TODO: переделать.  Использовать метод "insert_plog_door_open" для работы с backend plog
            $plogDoorOpen = $db->insert("insert into plog_door_open (date, ip, event, door, detail, expire) values (:date, :ip, :event, :door, :detail, :expire)", [
                "date" => (int)$date,
                "ip" => (string)$ip,
                "event" => (int)$event,
                "door" => (int)$door,
                "detail" => (string)$detail,
                "expire" => (int)$expire,
            ]);
            response(201, ["id" => $plogDoorOpen]);
            break;

        case $event['OPEN_BY_CALL']:
            //TODO:функционал белого кролика. Отккрытие двери по звонку из приложеня
            //[49704] Opening door by DTMF command for apartment 1
            response(200);
            break;
    }

    exit();

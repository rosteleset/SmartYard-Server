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
            $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door,$detail);
            response(201, ["id" => $plogDoorOpen]);
            break;

        case $events['OPEN_BY_CALL']:
            /* Пример события: "[49704] Opening door by DTMF command for apartment 1"
             */
            response(200);
            break;
        case $events['OPEN_BY_BUTTON']:
            /*TODO: открытие входной двери или калитки из нутри.
             * Отправляем событие FRS сереру для игнорирования детекции движения в момент когда человек
             * будет выходить из двери или калитки.
             * 'https://frs-server.dev/doorIsOpen' payload:{stream_id}
             * "Alt door opened by button press" - проверяем оборудована ли дополнительная дверь устройством детекции движения (камера)
             * "104:Main door opened by button press."
            */
            response(200);
            break;
    }

    exit();

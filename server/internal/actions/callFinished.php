<?php
    //Наполняем событиями с панели домофона в таблицу internal.plog_call_done
    //event: "All calls are done for apartment 123"
    [
        "date" => $date,
        "ip" => $ip,
        "call_id" => $call_id,
        "expire" => $expire
    ] = $postdata;

    if (!isset($date, $ip, $expire)) {
        response(406, "Invalid payload");
        exit();
    }

    //TODO: переделать.  Использовать метода "insert_plog_call_done" для работы с backend plog
    $callFinished = $db->insert("insert into plog_call_done (date, ip, call_id, expire) values (:date, :ip, :call_id, :expire)", [
            "date" => (int)$date,
            "ip" => (string)$ip,
            "call_id" => (int)$call_id,
            "expire" => (int)$expire,
        ]);

    response(201, ["id" => $callFinished]);
    exit();
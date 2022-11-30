<?php
    //Наполняем событиями с панели домофона в таблицу internal.plog_call_done
    //event: "All calls are done for apartment 123"
    [
        "date" => $date,
        "ip" => $ip,
        "call_id" => $call_id
    ] = $postdata;

    if (!isset($date, $ip)) {
        response(406, "Invalid payload");
        exit();
    }

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date,$ip,$call_id);
    response(201, ["id"=> $callDone]);
    exit();
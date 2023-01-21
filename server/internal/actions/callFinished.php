<?php
    // store events to db plog_call_done
    if (!isset(
        $postdata["date"],
        $postdata["ip"],
    )) {
        response(406, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "callId" => $callId
    ] = $postdata;

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $callId);

    response(201, ["id" => $callDone]);
    exit();
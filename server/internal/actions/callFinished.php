<?php
    // store events to db plog_call_done

    [
        "date" => $date,
        "ip" => $ip,
        "callId" => $callId
    ] = $postdata;

    if (!isset($date, $ip)) {
        response(406, "Invalid payload");
        exit();
    }

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $callId);
    response(201, ["id" => $callDone]);
    exit();
<?php
    // store events to db plog_call_done
    if (!isset($postdata["date"]) || !isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "subId" => $subId,
        "callId" => $callId,
    ] = $postdata;

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $subId, $callId);

    response(201, ["id" => $callDone]);
    exit();
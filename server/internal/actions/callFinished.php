<?php
    // store events to db 'plog_call_done'
    if (!isset($postdata["date"]) || !isset($postdata["ip"]) && !isset($postdata["subId"])) {
        response(406, false, false, "Invalid payload");
        exit();
    }

    [
        "date" => $date,
        "ip" => $ip,
        "subId" => $subId,
        "callId" => $callId,
    ] = $postdata;

    // Validate IP or subId presence
    if (!($ip || $subId)) {
        response(406, false, false, "Invalid payload: not valid 'ip' or 'subId'");
        exit();
    }

    $plog = loadBackend("plog");

    $callDone = $plog->addCallDoneData($date, $ip, $subId, $callId);

    response(204);
    exit();
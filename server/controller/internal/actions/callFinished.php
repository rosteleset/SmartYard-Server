<?php
// store events to db plog_call_done
if (!isset(
    $postdata["date"],
    $postdata["ip"],
)) {
    response(406, "Invalid payload");
}

[
    "date" => $date,
    "ip" => $ip,
    "callId" => $callId
] = $postdata;

$plog = backend("plog");

$plog->addCallDoneData($date, $ip, $callId);

response(200);

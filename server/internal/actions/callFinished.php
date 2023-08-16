<?php
// store events to db plog_call_done
use logger\Logger;

$logger = Logger::channel('internal', 'callFinished');

if (!isset(
    $postdata["date"],
    $postdata["ip"],
)) {
    $logger->debug('invalid post data', ['data' => $postdata ?? []]);

    response(406, "Invalid payload");
}

[
    "date" => $date,
    "ip" => $ip,
    "callId" => $callId
] = $postdata;

$plog = loadBackend("plog");

$callDone = $plog->addCallDoneData($date, $ip, $callId);

$logger->debug('success callFinished', ['data' => $postdata]);

response(201, ["id" => $callDone]);

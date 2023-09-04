<?php
if (!isset($postdata["date"], $postdata["ip"],))
    return response(406, "Invalid payload");

["date" => $date, "ip" => $ip, "callId" => $callId] = $postdata;

$plog = backend("plog");

$plog->addCallDoneData($date, $ip, $callId);

return response(200);
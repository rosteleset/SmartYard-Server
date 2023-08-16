<?php
/*Store events to db plog_door_open.
     "freeze motion detection" request for SRS
    */
if (!isset(
    $postdata["date"],
    $postdata["ip"],
    $postdata["event"],
    $postdata["door"],
    $postdata["detail"],
)) {
    response(406, "Invalid payload");
}

[
    "date" => $date,
    "ip" => $ip,
    "event" => $event,
    "door" => $door,
    "detail" => $detail
] = $postdata;

if (!isset($date, $ip, $event, $door, $detail)) {
    response(406, "Invalid payload");
}

//TODO: refactor events code ?!
try {
    $events = @json_decode(file_get_contents(__DIR__ . "/../../syslog/utils/events.json"), true);
} catch (Exception $e) {
    error_log(print_r($e, true));
    response(555, [
        "error" => "events config is missing",
    ]);
}
$plog = loadBackend('plog');

switch ($event) {
    case $events['OPEN_BY_KEY']:
    case $events['OPEN_BY_CODE']:
        $plogDoorOpen = $plog->addDoorOpenData($date, $ip, $event, $door, $detail);
        response(201, ["id" => $plogDoorOpen]);

    case $events['OPEN_BY_CALL']:
        response(200);

    case $events['OPEN_BY_BUTTON']:
        [0 => [
            "camera_id" => $streamId,
            "frs" => $frsUrl
        ]] = $db->get(
            'SELECT frs, camera_id FROM cameras 
                        WHERE camera_id = (
                        SELECT camera_id FROM houses_domophones 
                        LEFT JOIN houses_entrances USING (house_domophone_id)
                        WHERE ip = :ip AND domophone_output = :door)',
            ["ip" => $ip, "door" => $door],
            []
        );

        if (isset($frsUrl)) {
            $payload = ["streamId" => strval($streamId)];
            $apiResponse = apiExec("POST", $frsUrl . "/api/doorIsOpen", $payload);

            response(201, $apiResponse);
        }

        response(200);
}

exit();

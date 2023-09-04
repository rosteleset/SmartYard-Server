<?php
/*Store events to db plog_door_open.
     "freeze motion detection" request for SRS
    */

use Selpol\Service\DatabaseService;

if (!isset(
    $postdata["date"],
    $postdata["ip"],
    $postdata["event"],
    $postdata["door"],
    $postdata["detail"],
)) {
    return response(406, "Invalid payload");
}

["date" => $date, "ip" => $ip, "event" => $event, "door" => $door, "detail" => $detail] = $postdata;

if (!isset($date, $ip, $event, $door, $detail)) {
    return response(406, "Invalid payload");
}

//TODO: refactor events code ?!
try {
    $events = @json_decode(file_get_contents(__DIR__ . "/../../syslog/utils/events.json"), true);
} catch (Exception $e) {
    error_log(print_r($e, true));
    return response(555, ["error" => "events config is missing",]);
}
$plog = backend('plog');

switch ($event) {
    case $events['OPEN_BY_KEY']:
    case $events['OPEN_BY_CODE']:
        $plog->addDoorOpenData($date, $ip, $event, $door, $detail);

    return response(200);

    case $events['OPEN_BY_CALL']:
        return response(200);

    case $events['OPEN_BY_BUTTON']:
        $db = container(DatabaseService::class);

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

            return response(201, $apiResponse);
        }

        return response(200);
}

exit();
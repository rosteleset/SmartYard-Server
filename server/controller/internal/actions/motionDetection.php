<?php
/** Get ip motion detection device, find stream_id and frs url.
 * Send POST request to FRS.
 */

use Selpol\Service\DatabaseService;

if (!isset($postdata["date"], $postdata["ip"], $postdata["motionActive"]))
    response(406, "Invalid payload");

$db = container(DatabaseService::class);

$logger = logger('motion');

["date" => $date, "ip" => $ip, "motionActive" => $motionActive] = $postdata;

$query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND ip = :ip';
$params = ["ip" => $ip, "frs" => "-"];
$result = $db->get($query, $params, []);

if (!$result) {
    $logger->debug('Motion detection not enabled', ['frs' => '-', 'ip' => $ip]);

    response(200, "FRS not enabled on this stream");
}

[0 => ["camera_id" => $streamId, "frs" => $frsUrl]] = $result;

$payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

$logger->debug('Motion detection', $payload);

$apiResponse = apiExec("POST", $frsUrl . "/api/motionDetection", $payload);
response(201, $apiResponse);
<?php

namespace Selpol\Controller\Internal;

use Exception;
use Selpol\Controller\Controller;
use Selpol\Http\Response;
use Selpol\Service\DatabaseService;
use Selpol\Service\FrsService;

class ActionController extends Controller
{
    public function callFinished(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["date"], $body["ip"],))
            return $this->rbtResponse(400);

        ["date" => $date, "ip" => $ip, "callId" => $callId] = $body;

        backend("plog")->addCallDoneData($date, $ip, $callId);

        return $this->rbtResponse();
    }

    public function motionDetection(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["date"], $body["ip"], $body["motionActive"]))
            return $this->response(400);

        $db = container(DatabaseService::class);

        $logger = logger('motion');

        ["date" => $date, "ip" => $ip, "motionActive" => $motionActive] = $body;

        $query = 'SELECT camera_id, frs FROM cameras WHERE frs != :frs AND ip = :ip';
        $params = ["ip" => $ip, "frs" => "-"];
        $result = $db->get($query, $params, []);

        if (!$result) {
            $logger->debug('Motion detection not enabled', ['frs' => '-', 'ip' => $ip]);

            return $this->rbtResponse();
        }

        [0 => ["camera_id" => $streamId, "frs" => $frsUrl]] = $result;

        $payload = ["streamId" => $streamId, "start" => $motionActive ? "t" : "f"];

        $apiResponse = container(FrsService::class)->request('POST', $frsUrl . "/api/motionDetection", $payload);

        return $this->rbtResponse(201, $apiResponse);
    }

    public function openDoor(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset($body["date"], $body["ip"], $body["event"], $body["door"], $body["detail"])) return $this->rbtResponse(400);

        ["date" => $date, "ip" => $ip, "event" => $event, "door" => $door, "detail" => $detail] = $body;

        if (!isset($date, $ip, $event, $door, $detail)) return $this->rbtResponse(400);

        try {
            $events = @json_decode(file_get_contents(__DIR__ . "/../../syslog/utils/events.json"), true);
        } catch (Exception $e) {
            error_log(print_r($e, true));

            return $this->rbtResponse(500);
        }

        $plog = backend('plog');

        switch ($event) {
            case $events['OPEN_BY_KEY']:
            case $events['OPEN_BY_CODE']:
                $plog->addDoorOpenData($date, $ip, $event, $door, $detail);

                return $this->rbtResponse();

            case $events['OPEN_BY_CALL']:
                return $this->rbtResponse();

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
                    $apiResponse = container(FrsService::class)->request('POST', $frsUrl . "/api/doorIsOpen", $payload);

                    return $this->rbtResponse(201, $apiResponse);
                }

                return $this->response(204);
        }

        return $this->response(204);
    }

    public function setRabbitGates(): Response
    {
        $body = $this->request->getParsedBody();

        if (!isset(
            $body["ip"],
            $body["prefix"],
            $body["apartmentNumber"],
            $body["apartmentId"],
            $body["date"],
        ))
            return $this->rbtResponse(406);

        ["ip" => $ip, "prefix" => $prefix, "apartmentNumber" => $apartment_number, "apartmentId" => $apartment_id, "date" => $date,] = $body;

        $query = "UPDATE houses_flats SET last_opened = :last_opened
        WHERE (flat = :flat OR house_flat_id = :house_flat_id) AND white_rabbit > 0 AND address_house_id = (
        SELECT address_house_id from houses_houses_entrances 
        WHERE prefix = :prefix AND house_entrance_id = (
        SELECT house_entrance_id FROM houses_domophones LEFT JOIN houses_entrances USING (house_domophone_id) 
        WHERE ip = :ip AND entrance_type = 'wicket'))";
        $params = [
            "ip" => $ip,
            "flat" => $apartment_number,
            "house_flat_id" => $apartment_id,
            "prefix" => $prefix,
            "last_opened" => $date,
        ];

        $result = container(DatabaseService::class)->modify($query, $params);

        return $this->rbtResponse(202, ['id' => $result]);
    }

    public function getSyslogConfig(): Response
    {
        $config = config();

        $payload = ['clickhouseService' => [
            'host' => $config['backends']['plog']['host'],
            'port' => $config['backends']['plog']['port'],
            'database' => $config['backends']['plog']['database'],
            'username' => $config['backends']['plog']['username'],
            'password' => $config['backends']['plog']['password'],
        ], 'hw' => $config['syslog_servers']];

        return $this->rbtResponse(data: $payload);
    }
}
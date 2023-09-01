<?php

use backends\plog\plog;
use backends\frs\frs;
use Selpol\Service\DomophoneService;
use Selpol\Service\RedisService;

$logger = logger('internal');

$frs = backend("frs");
$households = backend("households");

$config = config();
$redis = container(RedisService::class)->getRedis();

$camera_id = $_GET['stream_id'];
$face_id = (int)$postdata[frs::P_FACE_ID];
$event_id = (int)$postdata[frs::P_EVENT_ID];

if (!isset($camera_id) || $face_id == 0 || $event_id == 0) {
    $logger->debug('Send empty data');

    response(204);
}

$frs_key = "frs_key_" . $camera_id;
if ($redis->get($frs_key) != null) {
    $logger->debug('redis frs key empty', ['key' => $frs_key]);

    response(204);
}

$entrance = $frs->getEntranceByCameraId($camera_id);

if (!$entrance) {
    $logger->debug('entrance is empty', ['camera' => $camera_id]);

    response(204);
}

$flats = $frs->getFlatsByFaceId($face_id, $entrance["entranceId"]);

if (!$flats) {
    $logger->debug('flats is empty', ['entrance' => $entrance['entranceId']]);

    response(204);
}

// TODO: check if FRS is allowed for flats

$domophone_id = $entrance["domophoneId"];
$domophone_output = $entrance["domophoneOutput"];
$domophone = $households->getDomophone($domophone_id);

try {
    $logger->debug('Try open door', ['frs_key' => $frs_key]);

    $model = container(DomophoneService::class)->model($domophone["model"], $domophone["url"], $domophone["credentials"]);
    $model->open_door($domophone_output);
    $redis->set($frs_key, 1, $config["backends"]["frs"]["open_door_timeout"]);
    $plog = backend("plog");
    if ($plog) {
        $plog->addDoorOpenDataById(time(), $domophone_id, plog::EVENT_OPENED_BY_FACE, $domophone_output,
            $face_id . "|" . $event_id);

        $logger->debug('Door open', ['domophone' => $domophone_id]);
    }
} catch (\Exception $e) {
    $logger->error('Error open door' . PHP_EOL . $e);

    response(404, false, 'Ошибка', 'Домофон недоступен');
}

response(204);
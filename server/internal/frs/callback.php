<?php

use backends\plog\plog;
use backends\frs\frs;

require_once __DIR__ . '/../../utils/checkint.php';

$frs = loadBackend("frs");
$households = loadBackend("households");

$camera_id = $_GET['stream_id'];
$face_id = (int)$postdata[frs::P_FACE_ID];
$event_id = (int)$postdata[frs::P_EVENT_ID];

if (!isset($camera_id) || $face_id == 0 || $event_id == 0)
    response(204);

$frs_key = "frs_key_" . $camera_id;
if ($redis->get($frs_key) != null)
    response(204);

$entrances = $households->getEntrances('cameraId', ['cameraId' => $camera_id]);
if (!$entrances)
    response(204);

// open all linked to the camera domophones but generate only one event
$has_event = false;
foreach ($entrances as $entrance) {
    $flats = $frs->getFlatsByFaceId($face_id, $entrance["entranceId"]);
    if (!$flats)
        continue;

    // TODO: check if FRS is allowed for flats

    $domophone_id = $entrance["domophoneId"];
    $domophone_output = $entrance["domophoneOutput"];
    $domophone = $households->getDomophone($domophone_id);
    try {
        $model = loadDomophone($domophone["model"], $domophone["url"], $domophone["credentials"]);
        $model->open_door($domophone_output);
        if (!$has_event) {
            $has_event = true;
            $redis->set($frs_key, 1, $config["backends"]["frs"]["open_door_timeout"]);
            $plog = loadBackend("plog");
            if ($plog) {
                $plog->addDoorOpenDataById(time(), $domophone_id, plog::EVENT_OPENED_BY_FACE, $domophone_output,
                    $face_id . "|" . $event_id);
            }
        }
    }
    catch (\Exception $e) {
        response(404, false, 'Ошибка', 'Домофон недоступен');
    }
}

response(204);

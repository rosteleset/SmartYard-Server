<?php

use backends\plog\plog;
use backends\frs\frs;

require_once __DIR__ . '/../../utils/checkint.php';

$households = loadBackend("households");
$plog = loadBackend("plog");
$frs = loadBackend("frs");

$camera_id = (string)$postdata[frs::P_STREAM_ID];
$event_id = (int)$postdata[frs::P_EVENT_ID];
$has_special = (bool)$postdata[frs::P_HAS_SPECIAL];
$plates = $postdata[frs::P_PLATES] ?? null;

if (!isset($camera_id) || $event_id == 0) {
    response(204);
}

$frs_key = "frs_key_" . $camera_id;
if ($redis->get($frs_key) != null) {
    response(204);
}

function openDoor($entrance): void
{
    global $households, $redis, $frs_key;

    $domophone_id = $entrance["domophoneId"];
    $domophone_output = $entrance["domophoneOutput"];
    $domophone = $households->getDomophone($domophone_id);
    try {
        $model = loadDevice('domophone', $domophone["model"], $domophone["url"], $domophone["credentials"]);
        $model->openLock($domophone_output);
        if (isset($config["backends"]["frs"]["open_gates_timeout"])) {
            $redis->set($frs_key, 1, $config["backends"]["frs"]["open_gates_timeout"]);
        }
    }
    catch (\Exception $e) {
        response(404, false, i18n("mobile.error"), i18n("mobile.unavailable"));
    }
}

$camera = $households->getCameras("id", $camera_id)[0];
$flag_no_registration = False;
if ($camera['frsMode'] == 2) {
    $flag_no_registration = True;
}

$entrances = [];
$entrance_ids = [];
foreach ($households->getEntrances('cameraId', ['cameraId' => $camera_id]) as $item) {
    $entrances[$item['entranceId']] = $item;
    $entrance_ids[] = $item['entranceId'];
    if ($has_special || $flag_no_registration) {
        openDoor($item);
    }
}

// do not create an event in case of special vehicle or camera is working in detection mode
if ($has_special || $flag_no_registration) {
    response(204);
}

if (!$entrances) {
    response(204);
}

if (!isset($plates)) {
    response(204);
}

$flats = [];
$e_flats = [];
$number = "";
foreach ($plates as $plate) {
    $f = $households->getFlats("car", ["number" => (string)$plate["number"]]);
    foreach ($f as $item) {
        if ($number === "") {
            $number = (string)$plate["number"];
        }
        $is_allowed = ($item['autoBlock'] === 0 && $item['manualBlock'] === 0 && $item['adminBlock'] === 0);
        if ($is_allowed) {
            foreach ($item['entrances'] as $entrance) {
                $e_id = $entrance['entranceId'];
                if (in_array($e_id, $entrance_ids)) {
                    $flats[(int)$item["flatId"]] = $item;
                    $e_flats[$e_id][] = $item['flatId'];
                }
            }
        }
    }
}

$flats_with_event = [];
// open all entrances in $e_flats
foreach ($e_flats as $key => $values) {
    $entrance = $entrances[$key];
    openDoor($entrance);
    $domophone_id = $entrance["domophoneId"];
    $domophone_output = $entrance["domophoneOutput"];
    if ($plog) {
        foreach ($values as $value) {
            if (!in_array($value, $flats_with_event)) {
                $flats_with_event[] = $value;
                $plog->addDoorOpenDataById(time(), $domophone_id, plog::EVENT_OPENED_BY_VEHICLE, $domophone_output,
                    $number . "|" . $value . "|" . $event_id);
            }
        }
    }
}

response(204);

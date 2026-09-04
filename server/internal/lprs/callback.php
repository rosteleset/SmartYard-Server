<?php

use backends\{
    frs\frs,
    plog\plog,
};

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
    return;
    global $households, $redis, $frs_key, $config;

    $domophone_id = $entrance["domophoneId"];
    $domophone_output = $entrance["domophoneOutput"];
    $domophone = $households->getDomophone($domophone_id);
    try {
        $device = loadDevice(
            type: 'domophone',
            model: $domophone['model'],
            url: $domophone['url'],
            password: $domophone['credentials'],
            lazy: $domophone['model'] !== 'sputnik.json', // Sputnik needs getSysinfo() to get its UUID for API calls
        );

        $device->openLock($domophone_output);

        if (isset($config["backends"]["frs"]["open_gates_timeout"])) {
            $redis->set($frs_key, 1, $config["backends"]["frs"]["open_gates_timeout"]);
        }
    } catch (Throwable) {
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
$flat_numbers = [];
foreach ($plates as $plate) {
    $plate_number = (string)$plate["number"];
    $country_code = substr((string)$plate["type"], 0, 2);
    $f = $households->getFlats("car", ["number" => $plate_number, "countryCode" => $country_code]);
    foreach ($f as $item) {
        $flat_id = (int)$item["flatId"];
        if (!isset($flat_numbers[$flat_id])) {
            $flat_numbers[$flat_id] = $plate_number;
        }
        $is_allowed = ($item['autoBlock'] === 0 && $item['manualBlock'] === 0 && $item['adminBlock'] === 0);
        if ($is_allowed) {
            foreach ($item['entrances'] as $entrance) {
                $e_id = $entrance['entranceId'];
                if (in_array($e_id, $entrance_ids)) {
                    $flats[$flat_id] = $item;
                    if (!isset($e_flats[$e_id])) {
                        $e_flats[$e_id] = [];
                    }
                    if (!in_array($flat_id, $e_flats[$e_id])) {
                        $e_flats[$e_id][] = $flat_id;
                    }
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
                $number = $flat_numbers[$value] ?? "";
                $plog->addDoorOpenDataById(time(), $domophone_id, plog::EVENT_OPENED_BY_VEHICLE, $domophone_output,
                    $number . "|" . $value . "|" . $event_id . "|" . $camera_id);

                // paranoidEvent (pushes)
                $households->paranoidEvent($entrance["entranceId"], "lp", $number);
            }
        }
    }
}

response(204);

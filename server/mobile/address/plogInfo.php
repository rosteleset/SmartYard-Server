<?php

use backends\plog\plog;

$plog = loadBackend("plog");
if (!$plog) {
    response(422);
}

$event_uuid = $param;
if (!$event_uuid) {
    response(405, false, i18n("mobile.404"));
}

$event_data = $plog->getEventDetails($event_uuid);
if (!$event_data) {
    response(404, false, i18n("mobile.404"));
}

if ($event_data[plog::COLUMN_PREVIEW] == plog::PREVIEW_NONE) {
    response(403, false, i18n("mobile.404"));
}

$flat_id = (int)$event_data[plog::COLUMN_FLAT_ID];
$households = loadBackend("households");
$flat_details = $households->getFlat($flat_id);
$domophone = json_decode($event_data[plog::COLUMN_DOMOPHONE]);

$event_info = [];
$event_info['uuid'] = $event_uuid;
$event_info['flatId'] = $flat_id;
$event_info['flatNumber'] = $flat_details['flat'];
if (isset($domophone->domophone_description) && $domophone->domophone_description !== false) {
    $event_info['entranceName'] = $domophone->domophone_description;
} else {
    $event_info['entranceName'] = '';
}
$event_info['eventType'] = $event_data[plog::COLUMN_EVENT];
if ((int)$event_data[plog::COLUMN_PREVIEW]) {
    $img_uuid = $event_data[plog::COLUMN_IMAGE_UUID];
    $url = @$config["api"]["mobile"] . "/address/plogCamshot/$img_uuid";
    $event_info['imageUrl'] = $url;
}

response(200, $event_info);

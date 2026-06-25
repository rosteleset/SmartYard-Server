<?php

/**
 * @api {post} /mobile/address/openDoor открыть дверь (калитку, ворота, шлагбаум)
 * @apiVersion 1.0.0
 * @apiDescription **нуждается в доработке**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiBody {Number} domophoneId идентификатор домофона
 * @apiBody {Number=0,1,2} [doorId=0] идентификатор двери (калитки, ворот, шлагбаума)
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

auth();

$domophone_id = (int)@$postdata['domophoneId'];
$door_id = (int)@$postdata['doorId'];
$households = loadBackend("households");
$entrance_id = null;

// Check intercom is blocking
$blocked = true;
foreach ($subscriber['flats'] as $flat) {
    $flatDetail = $households->getFlat($flat['flatId']);
    if ($flatDetail['autoBlock'] || $flatDetail['adminBlock']) {
        continue;
    }

    foreach ($flatDetail['entrances'] as $entrance) {
        $domophoneId = intval($entrance['domophoneId']);
        $e = $households->getEntrance($entrance['entranceId']);
        $doorId = intval($e['domophoneOutput']);
        if ($domophone_id == $domophoneId && $door_id == $doorId && !$flatDetail['manualBlock']) {
            $blocked = false;
            $entrance_id = $entrance['entranceId'];
            break;
        }
    }

    if (!$blocked) {
        break;
    }
}

if ($blocked) {
    response(404, false, i18n('mobile.404'), i18n('mobile.serviceUnavailable'));
}

$plog = loadBackend('plog');
$domophone = $households->getDomophone($domophone_id);
$doorOpeningUrls = (array)($domophone['ext']?->doorOpeningUrls ?? []);

// Try opening the door using the doorOpeningUrls attribute
if (isset($doorOpeningUrls[$door_id])) {
    $url = $doorOpeningUrls[$door_id];

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        error_log("Door opening URL validation error for intercom id=$domophone_id ($url)");
        response(404, false, i18n('mobile.error'), i18n('mobile.unavailable'));
    }

    $response = @file_get_contents($url, context: stream_context_create([
        'http' => ['timeout' => 3.0],
    ]));

    if ($response === false) {
        error_log("Error opening door for intercom id=$domophone_id via $url");
        response(404, false, i18n('mobile.error'), i18n('mobile.unavailable'));
    }

    if ($plog) {
        $plog->addDoorOpenDataById(time(), $domophone_id, $plog::EVENT_OPENED_BY_APP, $door_id, $subscriber['mobile']);

        // paranoidEvent (pushes)
        if (isset($entrance_id)) {
            $households->paranoidEvent($entrance_id, "app", $subscriber['mobile']);
        }
    }

    response();
}

// Try opening the door using the device method
try {
    $device = loadDevice(
        type: 'domophone',
        model: $domophone['model'],
        url: $domophone['url'],
        password: $domophone['credentials'],
        lazy: $domophone['model'] !== 'sputnik.json', // Sputnik needs getSysinfo() to get its UUID for API calls
    );

    $device->openLock($door_id);

    if ($plog) {
        $plog->addDoorOpenDataById(time(), $domophone_id, $plog::EVENT_OPENED_BY_APP, $door_id, $subscriber['mobile']);

        // paranoidEvent (pushes)
        if (isset($entrance_id)) {
            $households->paranoidEvent($entrance_id, "app", $subscriber['mobile']);
        }
    }
} catch (Throwable $e) {
    response(404, false, i18n('mobile.error'), i18n('mobile.unavailable'));
}

response();

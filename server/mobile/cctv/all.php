<?php

/**
 * @api {post} /cctv/all получить список камер
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {Number} [houseId] идентификатор дома
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив камер
 * @apiSuccess {Number} [-.houseId] идентификатор дома
 * @apiSuccess {Number} -.id id камеры
 * @apiSuccess {String} -.name наименование камеры
 * @apiSuccess {Number} -.lat широта
 * @apiSuccess {Number} -.lon долгота
 * @apiSuccess {String} -.url базовый url потока
 * @apiSuccess {String} -.token token авторизации
 * @apiSuccess {String} [-.serverType] тип DVR сервера: "flussonic" (default), "nimble", "trassir", "macroscop", "forpost"
 * @apiSuccess {String} [-.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
 */

auth();

$ret = [];

$house_id = (int)@$postdata['houseId'];
$households = loadBackend("households");
$cameras = loadBackend("cameras");

$houses = [];

/**
 * Get stub url's from config
 * payment_require_url - stub if flat is blocked
 * service_url - stub if camera  disabled
 * fallback_url - stub if set not valid DVR url
 */
$stub = $config['backends']['dvr']['stub'];

/**
 * Replace DVR url handler.
 * - Flat is blocked - replace DVR stream url to paymentRequireUrl stub
 * - IP camera disabled in admin panel - replace DVR stream url to serviceUrl stub
 * - DVR stream invalid - replace DVR stream url to fallbackUrl stub
 * @param array $cams
 * @param bool $flatIsBlocked
 * @param string $paymentRequireUrl
 * @param string $serviceUrl
 * @param string $fallbackUrl
 * @return array
 */
function replace_url(array $cams, bool $flatIsBlocked, string $paymentRequireUrl, string $serviceUrl, string $fallbackUrl ): array
{
    $result = [];
    foreach ($cams as $cam) {
        if ($cam['enabled'] === 0) {
            $cam['dvrStream'] = $serviceUrl;
        }
        if (filter_var($cam['dvrStream'], FILTER_VALIDATE_URL) === false) {
            $cam['dvrStream'] = $fallbackUrl;
        }
        if ($flatIsBlocked ) {
            $cam['dvrStream'] = $paymentRequireUrl;
        }
        $result[] = $cam;
    }
    return $result;
};

foreach($subscriber['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];
    if ($house_id != $houseId && $house_id != 0)
        continue;

    $flatDetail = $households->getFlat($flat['flatId']);
    $flatIsBlock = $flatDetail['adminBlock'] || $flatDetail['manualBlock'] || $flatDetail['autoBlock'];

    if (array_key_exists($houseId, $houses)) {
        $house = &$houses[$houseId];
        
    } else {
        $houses[$houseId] = [];
        $house = &$houses[$houseId];
        $house['houseId'] = strval($houseId);
        // TODO: добавить журнал событий.
        $house['cameras'] = $households->getCameras("houseId", $houseId);
        $house['doors'] = [];
    }

    $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));

    foreach ($flatDetail['entrances'] as $entrance) {
        if (array_key_exists($entrance['entranceId'], $house['doors'])) {
            continue;
        }
        
        $e = $households->getEntrance($entrance['entranceId']);
        $door = [];
        
        if ($e['cameraId']) {
            $cam = $cameras->getCamera($e["cameraId"]);
            $house['cameras'][] = $cam;
        }
        
        $house['doors'][$entrance['entranceId']] = $door;
    }

    if ($stub && $stub['payment_require_url'] && $stub['service_url'] && $stub['fallback_url']) {
        $house['cameras'] = replace_url(
            $house['cameras'],
            $flatIsBlock,
            $stub['payment_require_url'],
            $stub['service_url'],
            $stub['fallback_url']
        );
    }
}
$ret = [];
foreach($houses as $house_key => $h) {
    $houses[$house_key]['doors'] = array_values($h['doors']);
    unset( $houses[$house_key]['cameras']);
    foreach($h['cameras'] as $camera) {
        $dvr = loadBackend("dvr")->getDVRServerForCam($camera);
        $item = [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "url" => loadBackend("dvr")->getDVRStreamURLForCam($camera),
            "token" => loadBackend("dvr")->getDVRTokenForCam($camera, $subscriber['subscriberId']),
            "lon" => strval($camera['lon']),
            "serverType" => $dvr['type'],
            "hasSound" => boolval($camera['sound']),
        ];
        if (array_key_exists("hlsMode", $dvr)) {
            $item["hlsMode"] = $dvr["hlsMode"];
        }
        $ret[] = $item;
    }
}

// remove duplicates
$tempArr = array_unique(array_column($ret, 'id'));
$ret = array_values(array_intersect_key($ret, $tempArr));

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}

/*$ret = [
    [
        "id" => 1,
        "name" => "Тестовая камера",
        "lat" => "52.703267836456",
        "url" => "https://s5n3g69sluzg1.play-flussonic.cloud/2rfXfXQphj8-qLlkZWluhj8",
        "token" => "empty",
        "lon" => "41.4726675977"
    ]
];
*/

/*
all_cctv();

$ret = [];

$house_id = (int)@$postdata['houseId'];

if ($cams && $cams['cams']) {
    foreach ($cams['cams'] as $cam) {
        if (!$house_id || $cam['houseId'] == $house_id) {
            $cam['lon'] = $cam['lng'];
            unset($cam['lng']);
            unset($cam['clientId']);
            if ($house_id) {
                unset($cam['houseId']);
            }
            $ret[] = $cam;
        }
    }
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}
*/

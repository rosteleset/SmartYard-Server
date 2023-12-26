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
$stub_payment_require = $config['backends']['dvr']['stub']['payment_require_url'];  // stub if flat is blocked
$stub_service = $config['backends']['dvr']['stub']['service_url'];                  // stub if camera  disabled
$stub_fallback = $config['backends']['dvr']['stub']['fallback_url'];                // stub if set not valid DVR url

/**
 * Replace DVR url handler.
 * - flat blocked: stub 'service requires payment'
 * - DVR not set, or the camera is disabled: stub 'service / undergoing technical work'
 * @param array $cams
 * @param bool $flatIsBlocked
 * @param string $stub_payment_url
 * @param string $stub_service_url
 * @param string $stub_fallback
 * @return array
 */
function replace_url(array $cams, bool $flatIsBlocked, string $stub_payment_url, string $stub_service_url, string $stub_fallback ): array
{
    $result = [];
    foreach ($cams as $cam) {
        //check if stream enabled
        if ($cam['enabled'] === 0) {
            $cam['dvrStream'] = $stub_service_url;
        }
        // check the correct DVR url
        if (filter_var($cam['dvrStream'], FILTER_VALIDATE_URL) === false) {
            $cam['dvrStream'] = $stub_fallback;
        }
        //check flat is blocked
        if ($flatIsBlocked ) {
            $cam['dvrStream'] = $stub_payment_url;
        }
        $result[] = $cam;
    }
    return $result;
};

foreach($subscriber['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];
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

    $house['cameras'] = replace_url(
        $house['cameras'],
        $flatIsBlock,
        $stub_payment_require,
        $stub_service,
        $stub_fallback
    );
    
}
$ret = [];
foreach($houses as $house_key => $h) {
    $houses[$house_key]['doors'] = array_values($h['doors']);
    unset( $houses[$house_key]['cameras']);
    foreach($h['cameras'] as $camera) {
        $dvr = loadBackend("dvr")->getDVRServerByStream($camera['dvrStream']);
        $item = [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "url" => $camera['dvrStream'],
            "token" => loadBackend("dvr")->getDVRTokenForCam($camera, $subscriber['subscriberId']),
            "lon" => strval($camera['lon']),
            "serverType" => $dvr['type']
        ];
        if (array_key_exists("hlsMode", $dvr)) {
            $item["hlsMode"] = $dvr["hlsMode"];
        }
        $ret[] = $item;
    }
}

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

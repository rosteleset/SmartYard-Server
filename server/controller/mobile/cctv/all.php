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
 */

auth();

$ret = [];

$house_id = (int)@$postdata['houseId'];
$households = loadBackend("households");
$cameras = loadBackend("cameras");

$houses = [];

foreach($subscriber['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];
    
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

    $flatDetail = $households->getFlat($flat['flatId']);
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
    
}
$ret = [];
foreach($houses as $house_key => $h) {
    $houses[$house_key]['doors'] = array_values($h['doors']);
    unset( $houses[$house_key]['cameras']);
    foreach($h['cameras'] as $camera) {
        $dvr = loadBackend("dvr")->getDVRServerByStream($camera['dvrStream']);
        $ret[] = [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "url" => $camera['dvrStream'],
            "token" => loadBackend("dvr")->getDVRTokenForCam($camera, $subscriber['subscriberId']),
            "lon" => strval($camera['lon']),
            "serverType" => $dvr['type']
        ];
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

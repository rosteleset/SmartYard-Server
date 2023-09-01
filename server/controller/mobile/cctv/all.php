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

$user = auth();

$house_id = (int)@$postdata['houseId'];
$households = backend("households");
$cameras = backend("cameras");

$houses = [];

foreach ($user['flats'] as $flat) {
    if ($flat['addressHouseId'] != $house_id)
        continue;

    $houseId = $flat['addressHouseId'];

    if (array_key_exists($houseId, $houses)) {
        $house = &$houses[$houseId];

    } else {
        $houses[$houseId] = [];
        $house = &$houses[$houseId];
        $house['houseId'] = strval($houseId);

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

$result = [];

foreach ($houses as $house_key => $h) {
    $houses[$house_key]['doors'] = array_values($h['doors']);

    unset($houses[$house_key]['cameras']);

    foreach ($h['cameras'] as $camera) {
        if ($camera['cameraId'] === null)
            continue;

        $dvr = backend("dvr")->getDVRServerByStream($camera['dvrStream']);

        $result[] = [
            "id" => $camera['cameraId'],
            "name" => $camera['name'],
            "lat" => strval($camera['lat']),
            "url" => $camera['dvrStream'],
            "token" => backend("dvr")->getDVRTokenForCam($camera, $user['subscriberId']),
            "lon" => strval($camera['lon']),
            "serverType" => $dvr['type']
        ];
    }
}

if (count($result)) response(200, $result);
else response();
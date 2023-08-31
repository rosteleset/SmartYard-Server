<?php

/**
 * @api {post} /cctv/camMap отношения домофонов и камер
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup CCTV
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив c настройками
 * @apiSuccess {String} -.id id домофона
 * @apiSuccess {String} -.url url камеры
 * @apiSuccess {String} -.token токен
 * @apiSuccess {String="t","f"} -.frs подключен FRS
 * @apiSuccess {String="nimble","flussonic", "macroscop", "trassir"} [-.serverType] тип видео-сервера ('flussonic' by default)
 */
$user = auth();

$return = [];

$house_id = (int)@$postdata['houseId'];
$households = backend("households");
$cameras = backend("cameras");

$houses = [];
$cams = [];

foreach ($user['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];

    if (array_key_exists($houseId, $houses))
        $house = &$houses[$houseId];
    else {
        $houses[$houseId] = [];
        $house = &$houses[$houseId];
        $house['houseId'] = strval($houseId);
        $house['doors'] = [];
    }

    $flatDetail = $households->getFlat($flat['flatId']);

    foreach ($flatDetail['entrances'] as $entrance) {
        if (in_array($entrance['entranceId'], $house['doors']))
            continue;

        $e = $households->getEntrance($entrance['entranceId']);

        if ($e['cameraId'] && !array_key_exists($entrance['entranceId'], $cams)) {
            $cam = $cameras->getCamera($e["cameraId"]);
            $cams[$entrance['entranceId']] = $cam;
        }

        $house['doors'][] = $entrance['entranceId'];
    }
}

foreach ($cams as $entrance_id => $cam) {
    $e = $households->getEntrance($entrance_id);
    $dvr = backend("dvr")->getDVRServerByStream($cam['dvrStream']);
    $frs = 'f';
    $cameras = backend("cameras");

    if ($cameras) {
        $vstream = $cameras->getCamera($e['cameraId']);
        $frs = strlen($vstream["frs"]) > 1 ? 't' : 'f';
    }

    $return[] = [
        'id' => strval($e['domophoneId']),
        'url' => $cam['dvrStream'],
        'token' => backend("dvr")->getDVRTokenForCam($cam, $user['subscriberId']),
        'frs' => $frs,
        'serverType' => $dvr['type']
    ];
}

if (count($return)) response(200, $return);
else response();
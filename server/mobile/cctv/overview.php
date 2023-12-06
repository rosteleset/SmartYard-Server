<?php

/**
 * @api {post} /cctv/overview получить список видовых камер
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив камер
 * @apiSuccess {Number} -.id id камеры
 * @apiSuccess {String} -.name наименование камеры
 * @apiSuccess {Number} -.lat широта
 * @apiSuccess {Number} -.lon долгота
 * @apiSuccess {String} -.url базовый url потока
 * @apiSuccess {String} -.token token авторизации
 */

// TODO: add hlsMode
auth();

$cameras = loadBackend("cameras");
$dvr = loadBackend("dvr");

$common_cameras = $cameras->getCameras("common");
$resp = [];

foreach ($common_cameras as $camera) {
    $item = [
        "id" => $camera["cameraId"],
        "name" => $camera["name"],
        "lat" => strval($camera['lat']),
        "lon" => strval($camera['lon']),
        "url" => $camera['dvrStream'],
        "token" => $dvr->getDVRTokenForCam($camera, $subscriber['subscriberId']),
    ];
    $resp=[... $resp, $item];
}

response(200, $resp);

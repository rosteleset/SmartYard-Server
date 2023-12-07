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
 * @apiSuccess {String} [-.serverType] тип DVR сервера: "flussonic" (default), "nimble", "trassir", "macroscop", "forpost"
 * @apiSuccess {String} [-.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
 */

auth();
$allowedMods = ["fmp4", "mpegts"];
$cameras = loadBackend("cameras");
$dvr = loadBackend("dvr");

$common_cameras = $cameras->getCameras("common");
$resp = [];

foreach ($common_cameras as $camera) {
    $hlsMode = $dvr->getDVRServerByStream($camera['dvrStream'])["hlsMode"];
    $item = [
        "id" => $camera["cameraId"],
        "name" => $camera["name"],
        "lat" => strval($camera['lat']),
        "lon" => strval($camera['lon']),
        "url" => $camera['dvrStream'],
        "token" => $dvr->getDVRTokenForCam($camera, $subscriber['subscriberId']),
        "serverType" => $dvr->getDVRServerByStream($camera['dvrStream'])["type"],
    ];

    if ($hlsMode && in_array($hlsMode, $allowedMods)){
        $item["hlsMode"] = $hlsMode;
    }

    $resp=[... $resp, $item];
}

response(200, $resp);

<?php

    /**
     * @api {post} /mobile/cctv/overview получить список видовых камер
     * @apiVersion 1.0.0
     * @apiDescription **почти готов**
     *
     * @apiGroup CCTV
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Object[]} - массив камер
     * @apiSuccess {Number} -.id id камеры
     * @apiSuccess {String} -.name наименование камеры. Формат: название камеры/адрес установки. В приложении показывается на двух строчках.
     * @apiSuccess {Number} -.lat широта
     * @apiSuccess {Number} -.lon долгота
     * @apiSuccess {String} -.url базовый url потока
     * @apiSuccess {String} -.token token авторизации
     * @apiSuccess {String} [-.serverType] тип DVR сервера: "flussonic" (default), "nimble", "trassir", "macroscop", "forpost"
     * @apiSuccess {String} [-.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
     * @apiSuccess {Object} [-.parking] парковка камеры, только для парковочных камер (есть обработанный кадр со свободными местами)
     * @apiSuccess {Number} -.parking.capacity общее количество мест
     * @apiSuccess {Number} -.parking.free количество свободных мест
     * @apiSuccess {String="Y-m-d H:i:s"} -.parking.processedAt время последнего расчёта
     */

    auth();

    require_once __DIR__ . "/helpers/overviewCamera.php";

    $cameras = loadBackend("cameras");
    $dvr = loadBackend("dvr");
    $parking = loadBackend("parking");

    $common_cameras = $cameras->getCameras("common");
    $resp = [];
    $parkingCameras = [];

    if ($parking) {
        $parkingCameras = $parking->parkingCameras(array_column($common_cameras ?: [], "cameraId")) ?: [];
    }

    foreach ($common_cameras as $camera) {
        $item = mobile_overview_camera($camera, $dvr, $subscriber['subscriberId']);

        if (!$item) {
            continue;
        }

        if (array_key_exists($camera["cameraId"], $parkingCameras)) {
            $item["parking"] = $parkingCameras[$camera["cameraId"]];
        }

        $resp[] = $item;
    }

    response(200, $resp);

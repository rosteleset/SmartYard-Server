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
     */

    auth();
    $cameras = loadBackend("cameras");
    $dvr = loadBackend("dvr");

    $common_cameras = $cameras->getCameras("common");
    $resp = [];

    foreach ($common_cameras as $camera) {
        if ($camera['enabled']){
            $dvrServer = $dvr->getDVRServerForCam($camera);
            $url = $dvr->getDVRStreamURLForCam($camera);
            // skip not valid url
            if (!filter_var($url, FILTER_VALIDATE_URL)){
                continue;
            }

            $item = [
                "id" => $camera["cameraId"],
                "name" => $camera["name"],
                "lat" => (string)$camera['lat'],
                "lon" => (string)$camera['lon'],
                "url" => $url,
                "token" => $dvr->getDVRTokenForCam($camera, $subscriber['subscriberId']),
                "serverType" => $dvrServer["type"],
                "hasSound" => (bool)$camera['sound'],
            ];

            if (array_key_exists("hlsMode", $dvrServer)) {
                $item["hlsMode"] = $dvrServer['hlsMode'];
            }

            $resp[] = $item;
        }
    }

    response(200, $resp);

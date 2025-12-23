<?php

    /**
     * @api {post} /mobile/cctv/camMap отношения домофонов и камер
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup CCTV
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Object[]} - массив c настройками
     * @apiSuccess {String} [-.entranceId] идентификатор входа
     * @apiSuccess {String} -.id идентификатор домофона
     * @apiSuccess {String} -.url url камеры
     * @apiSuccess {String} -.token токен
     * @apiSuccess {String="t","f"} -.frs подключен FRS
     * @apiSuccess {String="nimble","flussonic","macroscop","trassir"} [-.serverType] тип видео-сервера ('flussonic' by default)
     * @apiSuccess {String="fmp4","mpegts"} [-.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
     * @apiSuccess {object} [-.altCameras] дополнительные камеры
     * @apiSuccess {String} -.altCameras.cameraId идентификатор камеры
     * @apiSuccess {String} -.altCameras.url url камеры
     * @apiSuccess {String} -.altCameras.token токен авторизации на медиа сервере
     * @apiSuccess {String="t","f"} -.altCameras.frs подключен FRS
     * @apiSuccess {String="nimble","flussonic","macroscop","trassir"} [-.altCameras.serverType] тип видео-сервера ('flussonic' by default)
     * @apiSuccess {String="fmp4","mpegts"} [-.altCameras.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
    */

    auth();

    $ret = [];

    $households = loadBackend("households");
    $cameras = loadBackend("cameras");
    $dvr = loadBackend("dvr");

    $entrances = [];

    foreach ($subscriber['flats'] as $flat) {
        $flat_id = $flat['flatId'];
        $r = $households->getEntrances('flatId', $flat_id);
        foreach ($r as $entrance) {
            $entrance_id = strval($entrance['entranceId']);
            if (array_key_exists($entrance_id, $entrances)) {
                continue;
            }

            $cam = $cameras->getCamera($entrance["cameraId"]);
            if ($cam) {
                $item = [
                    'entranceId' => $entrance_id,
                    'id' => strval($entrance['domophoneId']),
                    'url' => $dvr->getDVRStreamURLForCam($cam)  ?? '',
                    'token' => $dvr->getDVRTokenForCam($cam, $subscriber['subscriberId']) ?? '',
                    'frs' => strlen($cam["frs"]) > 1 ? 't' : 'f',
                    'serverType' => $dvr->getDVRServerForCam($cam)['type'] ?? 'flussonic',
                    'hasSound' => boolval($cam['sound']),
                ];

                // alternative cameras
                $alt_cameras = [];
                for ($i = 1; $i < 8; $i++) {
                    $cam = $cameras->getCamera($entrance["altCameraId$i"]);
                    if ($cam) {
                        $alt_cam = [
                            'cameraId' => strval($entrance["altCameraId$i"]),
                            'url' => $dvr->getDVRStreamURLForCam($cam) ?? '',
                            'token' => $dvr->getDVRTokenForCam($cam, $subscriber['subscriberId']) ?? '',
                            'frs' => strlen($cam["frs"]) > 1 ? 't' : 'f',
                            'serverType' => $dvr->getDVRServerForCam($cam)['type'] ?? 'flussonic',
                            'hasSound' => boolval($cam['sound']),
                        ];
                        $alt_cameras[] = $alt_cam;
                    }
                }
                if (count($alt_cameras) > 0) {
                    $item['altCameras'] = $alt_cameras;
                }

                $entrances[$entrance_id] = $item;
            }
        }
    }

    if (count($entrances)) {
        response(200, array_values($entrances));
    } else {
        response();
    }

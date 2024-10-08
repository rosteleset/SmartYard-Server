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
     * @apiSuccess {String="nimble","flussonic", "macroscop", "trassir"} [-.serverType] тип видео-сервера ('flussonic' by default)
     * @apiSuccess {String = "fmp4", "mpegts"} [-.hlsMode] режим HLS (used for flussonic only): "fmp4" (default for hevc support), "mpegts" (for flussonic below 21.02 version)
     */

    auth();

    $ret = [];

    $house_id = (int)@$postdata['houseId'];
    $households = loadBackend("households");
    $cameras = loadBackend("cameras");

    $houses = [];
    $cams = [];

    foreach ($subscriber['flats'] as $flat) {
        $houseId = $flat['addressHouseId'];

        if (array_key_exists($houseId, $houses)) {
            $house = &$houses[$houseId];

        } else {
            $houses[$houseId] = [];
            $house = &$houses[$houseId];
            $house['houseId'] = strval($houseId);
            $house['doors'] = [];
        }

        $flatDetail = $households->getFlat($flat['flatId']);
        foreach ($flatDetail['entrances'] as $entrance) {
            if (in_array($entrance['entranceId'], $house['doors'])) {
                continue;
            }

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
        $dvr = loadBackend("dvr")->getDVRServerForCam($cam);
        $frs = 'f';
        $cameras = loadBackend("cameras");
        if ($cameras) {
            $vstream = $cameras->getCamera($e['cameraId']);
            $frs = strlen($vstream["frs"]) > 1 ? 't' : 'f';
        }
        $item = [
            'entranceId' => strval($e['entranceId']),
            'id' => strval($e['domophoneId']),
            'url' => loadBackend("dvr")->getDVRStreamURLForCam($cam),
            'token' => loadBackend("dvr")->getDVRTokenForCam($cam, $subscriber['subscriberId']),
            'frs' => $frs,
            'serverType' => $dvr['type'],
            "hasSound" => boolval($cam['sound']),
        ];
        if (array_key_exists("hlsMode", $dvr)) {
            $item["hlsMode"] = $dvr["hlsMode"];
        }
        $ret[] = $item;
    }

    if (count($ret)) {
        response(200, $ret);
    } else {
        response();
    }

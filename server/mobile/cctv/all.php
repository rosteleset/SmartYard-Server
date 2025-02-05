<?php

    /**
     * @api {post} /mobile/cctv/all получить список камер
     * @apiVersion 1.0.0
     * @apiDescription **почти готов**
     *
     * @apiGroup CCTV
     *
     * @apiBody {Number} [houseId] идентификатор дома
     *
     * @apiBody {String} authorization токен авторизации
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

    $house_id = (int)@$postdata['houseId'];
    $households = loadBackend("households");

    require_once __DIR__ . "/helpers/listCameras.php";
    // at this point variable $ret contains camera data

    // remove duplicates
    $tempArr = array_unique(array_column($ret, 'id'));
    $ret = array_values(array_intersect_key($ret, $tempArr));

    // sort cameras by name
    usort($ret, function ($a, $b) {
        return $a['name'] > $b['name'];
    });

    if (count($ret)) {
        response(200, $ret);
    } else {
        response();
    }

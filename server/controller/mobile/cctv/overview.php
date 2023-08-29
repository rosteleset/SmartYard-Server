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

auth();
response();

/*
$ret = [];

$ocams = demo('overviewCams');

if ($ocams) {
    foreach ($ocams as $cam) {
        $cam['lon'] = $cam['lng'];
        unset($cam['lng']);
        unset($cam['clientId']);
        unset($cam['houseId']);
        $ret[] = $cam;
    }
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}
*/

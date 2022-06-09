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

auth();

all_cctv();

$ret = [];

$house_id = (int)@$postdata['houseId'];

if ($cams && $cams['cams']) {
    foreach ($cams['cams'] as $cam) {
        if (!$house_id || $cam['houseId'] == $house_id) {
            $cam['lon'] = $cam['lng'];
            unset($cam['lng']);
            unset($cam['clientId']);
            if ($house_id) {
                unset($cam['houseId']);
            }
            $ret[] = $cam;
        }
    }
}

if (count($ret)) {
    response(200, $ret);
} else {
    response();
}

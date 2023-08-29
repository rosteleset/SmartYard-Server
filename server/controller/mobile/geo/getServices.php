<?php

/**
 * @api {post} /geo/getServices список доступных услуг
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Geo
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} houseId дом
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.icon иконка услуги
 * @apiSuccess {String} -.title заголовок
 * @apiSuccess {String} -.description описание
 * @apiSuccess {String="t","f"} -.canChange доступна смена тарифа
 * @apiSuccess {String="t","f"} -.byDefault услуга предоставляется по умолчанию
 */

auth();

$house_id = (int)@$postdata['houseId'];

if (!$house_id) {
    response(422);
}
$households = loadBackend("households");

$ret = [];


if ($households->getFlats('houseId', $house_id)) {
    $s = $LanTa_services['domophone'];
    $s['byDefault'] = 't';
    $ret[] = $s;
}


if (count($ret)) {
    response(200, $ret);
} else {
    response();
}

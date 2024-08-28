<?php

/**
 * @api {post} /address/resetCode перегенерировать код открытия двери
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} flatId идентификатор квартиры
 *
 * @apiSuccess {Number} code новый код
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    $households = loadBackend("households");

    $flat_id = (int)@$postdata['flatId'];

    if (!$flat_id) {
        response(422);
    }
    $flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
    $f = in_array($flat_id, $flatIds);

    if (!$f) {
        response(404);
    }

    // TODO: allowDoorCode будет использоваться?

    $flat = $households->getFlat($flat_id);
    if ((@$flat['openCode'] ?: '') == '') {
        response(405);
    }

    $params = [];
    $params['openCode'] = '!';
    $households->modifyFlat($flat_id, $params);
    $flat = $households->getFlat($flat_id);

    response(200, ["code" => intval($flat['openCode'])]);

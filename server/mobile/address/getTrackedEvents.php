<?php

    /**
     * @api {post} /mobile/address/getTrackedEvents get the list of the tracked events
     * @apiVersion 1.0.0
     * @apiDescription **должен работать**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {integer} [flatId] идентификатор квартиры
     *
     * @apiSuccess {object[]} - массив объектов
     * @apiSuccess {integer} -.flatId идентификатор квартиры
     * @apiSuccess {integer} -.watcherId идентификатор наблюдения
     * @apiSuccess {integer="3 - открытие ключом","4 - открытие приложением","5 - открытие по морде лица","6 - открытие кодом открытия","9 - открытие по номеру машины"} -.eventType тип события
     * @apiSuccess {string} -.eventDetail детали события (ключ, номер телефона, идентификатор лица, номер машины)
     * @apiSuccess {string} -.comments комментарий наблюдения
     *
     * @apiErrorExample Ошибки
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     */

    auth();

    $flat_id = (int)$postdata['flatId'] ?? false;
    if (!$flat_id) {
        $flat_id = false;
    }

    if ($flat_id !== false) {
        $flat_ids = array_map(function($item) { return $item['flatId']; }, $subscriber['flats']);
        $f = in_array($flat_id, $flat_ids);
        if (!$f) {
            response(403, false, i18n("mobile.404"));
        }
    }

    $households = loadBackend("households");
    if (!$households) {
        response(422);
    }

    $data = [];
    $r = $households->watchers($device["deviceId"]);
    foreach ($r as $v) {
        $f_id = (int)$v["flatId"];
        if ($flat_id == $f_id || $flat_id === false) {
            $data[] = [
                "watcherId" => (int)$v["houseWatcherId"],
                "flatId" => $f_id,
                "eventType" => (int)$v["eventType"],
                "eventDetail" => $v["eventDetail"],
                "comments" => $v["comments"],
            ];
        }
    }

    if (count($data) > 0) {
        response(200, $data);
    }

    response();

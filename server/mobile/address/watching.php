<?php

    /**
     * @api {post} /mobile/address/watching list wathings
     * @apiVersion 1.0.0
     * @apiDescription **должен работать**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiSuccess {object[]} [-] массив объектов
     * @apiSuccess {integer} -.watcherId идентификатор наблюдения
     * @apiSuccess {integer} -.flatId идентификатор квартиры
     * @apiSuccess {integer="3 - открытие ключом","4 - открытие приложением","5 - открытие по морде лица","6 - открытие кодом открытия","9 - открытие по номеру машины"} -.eventType тип события
     * @apiSuccess {string} eventDetail детали события (ключ, номер телефона, идентификатор лица, номер машины)
     * @apiSuccess {string} comments комментарий наблюдения
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

    $data = [];
    $r = $households->watchers($device["deviceId"]);
    foreach ($r as $v) {
        $data[] = [
            "watcherId" => (int)$v["houseWatcherId"],
            "flatId" => (int)$v["flatId"],
            "evenType" => (int)$v["eventType"],
            "eventDetail" => $v["eventDetail"],
            "comments" => $v["comments"],
        ];
    }

    if (count($data) > 0) {
        response(200, $data);
    }

    response();

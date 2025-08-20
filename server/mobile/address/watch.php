<?php

    /**
     * @api {post} /mobile/address/watch watch for event
     * @apiVersion 1.0.0
     * @apiDescription **должен работать**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {integer} flatId идентификатор квартиры
     * @apiBody {integer="3 - открытие ключом","4 - открытие приложением","5 - открытие по морде лица","6 - открытие кодом открытия","9 - открытие по номеру машины"} -.eventType тип события
     * @apiBody {string} eventDetail детали события (ключ, номер телефона, идентификатор лица, номер машины)
     * @apiBody {string} comments комментарий наблюдения
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

    $households->watch($device["deviceId"], $flat_id, $postdata["eventType"], $postdata["eventDetail"], $postdata["comments"]);

    response();

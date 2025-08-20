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
     * @apiBody {integer} eventType
     * @apiBody {string} eventDetail
     * @apiBody {string} comments
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

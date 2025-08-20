<?php

    /**
     * @api {post} /mobile/address/unwatch stop watching for event
     * @apiVersion 1.0.0
     * @apiDescription **должен работать**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {integer} watcherId
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

    $watcher_id = (int)@$postdata['watcherId'];
    if (!$watcher_id) {
        response(422);
    }

    $households->unwatch($watcher_id, $device["deviceId"]);

    response();

<?php

/**
 * @api {post} /pay/bonus активировать бонусы
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Payments
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} clientId идентификатор абонента
 * @apiParam {Number=100,200,300} amount сколько активировать бонусов
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    response();

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


    $nominals = [
        100 => 100,
        200 => 210,
        300 => 330,
    ];

    $client_id = (int)$postdata['clientId'];
    $amount = (float)$postdata['amount'];

    $all_clients = all_clients();

    if (!$client_id || !in_array($client_id, $all_clients)) {
        response(404);
    }

    if (!@$nominals[$amount]) {
        response(422);
    }

    $balance = pg_fetch_result(pg_query("select balance from bonus_v2.balance where client_id = $client_id"), 0);

    if ($amount > $balance) {
        response(422);
    }

    pg_query("select bonus_v2.activate_bonus($client_id, $amount, {$nominals[$amount]})");
    pg_query("insert into webadmin.client_card_log (client_id, date, login, action) values ('$client_id', now(), 'dm', 'Подготовка бонусов к списанию')");

    response(200);


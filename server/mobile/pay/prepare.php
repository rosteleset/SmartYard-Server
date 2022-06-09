<?php

/**
 * @api {post} /pay/prepare подготовка к платежу
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Payments
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} clientId идентификатор клиента
 * @apiParam {Number} amount сумма платежа
 * @apiParam {String="rbs","dm"} type="dm" тип платежа
 *
 * @apiSuccess {String} - идентификатор платежа
 */

    auth();

    $client_id = (int)$postdata['clientId'];
    $amount = (float)$postdata['amount'];

    if ($client_id && $amount > 0) {
        if (@$postdata['type'] == 'rbs') {
            $id = (int)pg_fetch_result(pg_query("select nextval('rbs_payments_rbs_payment_id_seq')"), 0);
            pg_query("insert into rbs_payments (rbs_payment_id, client_id, amount, generate_date, last_update, source) values ({$id}, {$client_id}, {$amount}, now(), now(), '{$bearer['id']}@dm.lanta.me')");
        } else {
            pg_query("insert into domophones.payments (client_id, amount, bearer) values ($client_id, $amount, '{$bearer['id']}')");
            $id = md5(pg_fetch_result(pg_query("select currval(pg_get_serial_sequence('domophones.payments', 'domophone_payment_id'))"), 0));
        }

        response(200, (string)$id);
    } else {
        response(403);
    }

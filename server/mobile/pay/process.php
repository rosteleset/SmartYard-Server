<?php

/**
 * @api {post} /pay/process обработка платежа
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Payments
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} paymentId идентификатор платежа
 * @apiParam {String} sbId присвоенный сбером идентификатор
 *
 * @apiSuccess {String} - сообщение пользователю
 */

    auth();

    $payment_id = pg_escape_string($postdata['paymentId']);
    $sb_id = pg_escape_string($postdata['sbId']);

    if (!$sb_id || !$payment_id) {
        response(422);
    }

    $id = (int)pg_fetch_result(pg_query("select domophone_payment_id from domophones.payments where md_order is null and md5(domophone_payment_id::character varying)='$payment_id' and bearer='{$bearer['id']}'"), 0);

    if ($id) {
        pg_query("update domophones.payments set md_order='$sb_id' where domophone_payment_id=$id");
        response(200, "Ожидайте, платеж обрабатывается");
    } else {
        response(404);
    }

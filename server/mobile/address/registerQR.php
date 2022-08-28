<?php


/**
 * @api {post} /address/registerQR зарегистрировать QR код
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String} - показать alert c текстом
 *
 * @apiParam {String} QR QR код
 */

    auth();
    response(200, "");

/*
    $phone = $bearer['id'];
    $phone[0] = '8';

    $code = trim(@$postdata['QR']);

    if (!$code) {
        response(404);
    }

    $code = explode('/', $code);
    if ($code[2] == 'demo.lanta.me') {
        $code = (int)$code[count($code) - 1];
    } else {
        response(200, "QR-код не является кодом для доступа к квартире");
    }

    try {
        demo('registerQR', [ 'phone' => $phone, 'qr' => $code ], false);
        response(200, "Ваш запрос принят и будет обработан в течение одной минуты, пожалуйста подождите");
    } catch (Exception $ex) {
        response(520, false, $ex->getCode(), $ex->getMessage());
    }
*/

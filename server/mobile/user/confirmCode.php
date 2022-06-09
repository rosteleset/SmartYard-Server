<?php

/**
 * @api {post} /user/confirmCode подтвердить телефон
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiParam {String{11}} userPhone номер телефона
 * @apiParam {String{4}} smsCode код подтверждения
 *
 * @apiErrorExample Ошибки
 * 401 неверный код подтверждения
 * 404 запрос не найден
 * 422 неверный формат данных
 *
 * @apiSuccess {String} accessToken токен авторизации
 * @apiSuccess {Object[]} names имя и отчество
 * @apiSuccess {String} names.name имя
 * @apiSuccess {String} names.patronymic отчество
 */

    $user_phone = @$postdata['userPhone'];
    $user_phone[0] = '8';
    $pin = @$postdata['smsCode'];

    if (strlen($user_phone) == 11 && strlen($pin) == 4) {
        $user_phone = pg_escape_string($user_phone);
        $pinreq = pg_fetch_result(pg_query("select pin from domophones.pinreq where phone='$user_phone'"), 0);

        pg_query("update domophones.pinreq set attempts=attempts+1 where phone='$user_phone'");

        if (!$pinreq) {
            response(404);
        } else {
            if ($pinreq != $pin) {
                pg_query("delete from domophones.pinreq where attempts>5");
                response(403, false, "Пин-код введен неверно", "Пин-код введен неверно");
            } else {
                pg_query("delete from domophones.pinreq where phone='$user_phone'");
                $token = GUIDv4();
                if (!(int)pg_fetch_result(pg_query("select count(*) from domophones.bearers where id='$user_phone'"), 0)) {
                    pg_query("insert into domophones.bearers (id) values ('$user_phone')");
                }
                pg_query("update domophones.bearers set token='$token' where id='$user_phone'");
                response(200, [ 'accessToken' => $token, 'names' => pg_fetch_assoc(pg_query("select name, mname as patronymic from client_phone_names where phone='$user_phone'")) ]);
            }
        }
    } else {
        response(422);
    }

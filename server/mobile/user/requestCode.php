<?php

/**
 * @api {post} /user/requestCode запросить код подтверждения
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiParam {String{11}} userPhone номер телефона
 *
 * @apiErrorExample Ошибки
 * 422 неверный формат данных
 * 429 код уже запрошен
 */

    $user_phone = @$postdata['userPhone'];
    $user_phone[0] = '8';

    if (strlen($user_phone) == 11 && ctype_digit($user_phone)) {
        $user_phone = pg_escape_string($user_phone);
        $already = (int)pg_fetch_result(pg_query("select count(*) from domophones.pinreq where phone='$user_phone' and date + interval '60sec' > now()"), 0);
        if ($already) {
            response(429);
        } else {
            if ($user_phone == '89123456781') { // фейковый аккаунт №1
                $pin = '1001';
            } else
            if ($user_phone == '89123456782') { // фейковый аккаунт №2
                $pin = '1002';
            } else {
                $pin = sprintf("%04d", rand(0, 9999));
            }
            pg_query("select sms.send_sms_v2('$user_phone', '!Ваш код подтверждения: $pin')");
            pg_query("delete from domophones.pinreq where phone='$user_phone'");
            pg_query("insert into domophones.pinreq (phone, pin) values ('$user_phone', '$pin')");
            response();
        }
    } else {
        response(422);
    }

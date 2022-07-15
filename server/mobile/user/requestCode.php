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
    $isdn = loadBackend("isdn");

    if (strlen($user_phone) == 11 && ctype_digit($user_phone)) {

        $already = $redis->get("userpin_".$user_phone);
        if ($already){
            response(429);
        } else {
            if ($user_phone == '89123456781') { // фейковый аккаунт №1
                $pin = '1001';
            } else
            if ($user_phone == '89123456782') { // фейковый аккаунт №2
                $pin = '1002';
            } else {
                $pin = explode(":", $isdn->sendCode($user_phone))[0];
                $redis->setex("userpin_".$user_phone, 60, $pin);
            }
            // TODO: добавить в ответ способ подтверждения телефона, указанный в конфиге. (по умолчанию - по смс)
            response(); 
        }
    } else {
        response(422);
    }

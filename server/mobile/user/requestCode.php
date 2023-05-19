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
 * @apiSuccess {string="sms","outgoingCall"} [method="sms"] способ авторизации
 * @apiSuccess {string[]} [confirmationNumbers] список номеров для авторизации исходящим звонком (outgoingCall)
 *
 * @apiErrorExample Ошибки
 * 422 неверный формат данных
 * 429 код уже запрошен
 */

    $user_phone = @$postdata['userPhone'];
    if ($user_phone[0] == '8') { 
        $user_phone[0] = '7'; 
    }
    $isdn = loadBackend("isdn");

    if (ctype_digit($user_phone)) {

        $confirmMethod = @$config["backends"]["isdn"]["confirm_method"] ?: "outgoingCall";
        
        switch ($confirmMethod) {
            case 'outgoingCall':
                response(200, [ "method" => "outgoingCall", "confirmationNumbers" => $isdn->confirmNumbers()]);
                break;

            default:
                // smsCode - default
                $already = $redis->get("userpin_".$user_phone);
                if ($already){
                    response(429);
                } else {
                    if ($user_phone == '79123456781') { // фейковый аккаунт №1
                        $pin = '1001';
                    } else
                    if ($user_phone == '79123456782') { // фейковый аккаунт №2
                        $pin = '1002';
                    } else {
                        $pin = explode(":", $isdn->sendCode($user_phone))[0];
                    }
                    $redis->setex("userpin_".$user_phone, 60, $pin);
                    response();
                }
                break;
        }
    } else {
        response(422);
    }

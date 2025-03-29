<?php

    /**
     * @api {post} /mobile/user/requestCode запросить код подтверждения
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiBody {String{11}} userPhone номер телефона
     * @apiBody {String="sms","outgoingCall"} method номер телефона способ авторизации
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

    $headers = apache_request_headers();

    if (@$postdata['deviceToken']) {
        $device_token = "*" . $postdata['deviceToken'];
    } else if (@$headers['Accept-Language'] && @$headers['X-System-Info']) {
        $device_token = md5($headers['Accept-Language'] . $headers['X-System-Info']);
    } else {
        $device_token = 'default';
    }

    $isdn = loadBackend("isdn");
    $households = loadBackend("households");

    if (ctype_digit($user_phone)) {

        $confirmMethod = @$postdata['method'] ?: @$config["backends"]["isdn"]["confirm_method"] ?: "outgoingCall";

        // fake accounts - always confirmation by pin
        if (in_array($user_phone, @$config["backends"]["households"]["test_numbers"] ? : [])) {
            $pin = 1001 + array_search($user_phone, $config["backends"]["households"]["test_numbers"]);
            $redis->setex("userpin_" . $user_phone, 60, $pin);
            response(200, [ "method" => "sms" ]);
        }

        //TODO: add check for self_registering and existing number

        // real accounts
        switch ($confirmMethod) {
            case 'outgoingCall':
                response(200, [ "method" => "outgoingCall", "confirmationNumbers" => $isdn->confirmNumbers()]);
                break;

            default:
                // smsCode - default
                $already = $redis->get("userpin_" . $user_phone);
                if ($already){
                    response(429);
                } else {
                    $pin = explode(":", $isdn->sendCode($user_phone))[0];
                    $redis->setex("userpin_".$user_phone, 60, $pin);
                    response(200, [ "method" => $confirmMethod ]);
                }
                break;
        }
    } else {
        response(422);
    }

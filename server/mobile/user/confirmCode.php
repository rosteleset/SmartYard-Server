<?php

    /**
     * @api {post} /user/confirmCode подтвердить телефон
     * @apiVersion 1.0.0
     * @apiDescription **[метод готов]**
     *
     * @apiGroup User
     *
     * @apiParam {String{11}} userPhone номер телефона
     * @apiParam {String} deviceToken токен устройства
     * @apiParam {Number=0,1,2} platform тип клиента 0 - android, 1 - ios, 2 - web
     * @apiParam {String{4}} smsCode код подтверждения
     *
     * @apiErrorExample Ошибки
     * 401 неверный код подтверждения
     * 404 запрос не найден
     * 422 неверный формат данных
     *
     * @apiSuccess {String} accessToken токен авторизации
     * @apiSuccess {Object[]} names фамилия, имя, отчество
     * @apiSuccess {String} names.last фамилия
     * @apiSuccess {String} names.name имя
     * @apiSuccess {String} names.patronymic отчество

    */
    $user_phone = @$postdata['userPhone'];

    if ($user_phone[0] == '8') {
        $user_phone[0] = '7';
    }

    $device_token = @$postdata['deviceToken'] ?: 'default';
    $platform = @$postdata['platform'];
    $pin = @$postdata['smsCode'];
    $isdn = loadBackend("isdn");
    $inbox = loadBackend("inbox");
    $households = loadBackend("households");
    $confirmMethod = @$config["backends"]["isdn"]["confirm_method"] ?: "outgoingCall";

    if (strlen($pin) == 4) {
        $pinreq = $redis->get("userpin_" . $user_phone);

        $redis->setex("userpin.attempts_" . $user_phone, 3600, (int)$redis->get("userpin.attempts_" . $user_phone) + 1);

        if (!$pinreq) {
            response(404);
        } else {
            if ($pinreq != $pin) {
                $attempts = $redis->get("userpin.attempts_" . $user_phone);
                if ($attempts > 5) {
                    $redis->del("userpin_" . $user_phone);
                    $redis->del("userpin.attempts_" . $user_phone);
                    response(403, false, "Превышено максимальное число попыток ввода", "Превышено максимальное число попыток ввода");
                } else {
                    response(403, false, "Пин-код введен неверно", "Пин-код введен неверно");
                }
            } else {
                $redis->del("userpin_" . $user_phone);
                $redis->del("userpin.attempts_" . $user_phone);
                $token = GUIDv4();
                $subscribers = $households->getSubscribers("mobile", $user_phone);
                $devices = false;
                $subscriber_id = false;
                $names = ["name" => "", "patronymic" => "", "last" => ""];
                if ($subscribers) {
                    $subscriber = $subscribers[0];
                    // Пользователь найден
                    $subscriber_id = $subscriber["subscriberId"];
                    $names = ["name" => $subscriber["subscriberName"], "patronymic" => $subscriber["subscriberPatronymic"], "last" => $subscriber["subscriberLast"]];
                    $devices = $households->getDevices("subscriber", $subscriber_id);
                } else {
                    // Пользователь не найден - создаём
                    $subscriber_id = $households->addSubscriber($user_phone, "", "", "");
                }

                // no longer such a temporary solution
                if ($devices) {
                    $filteredDevices = array_filter($devices, function ($device) use ($device_token) {
                        return $device['deviceToken'] === $device_token;
                    });
                    $device = reset($filteredDevices);
                    if ($device) {
                        $households->modifyDevice($device["deviceId"], [ "authToken" => $token ]);
                    } else {
                        $households->addDevice($subscriber_id, $device_token, $platform, $token);
                        $inbox->sendMessage($subscriber_id, "Внимание!", "Произведена авторизация на новом устройстве", $action = "inbox");
                    }
                } else {
                    $households->addDevice($subscriber_id, $device_token, $platform, $token);
                    $inbox->sendMessage($subscriber_id, "Внимание!", "Произведена авторизация на новом устройстве", $action = "inbox");
                }

                response(200, ['accessToken' => $token, 'names' => $names]);
            }
        }
    } else {
        response(422);
    }

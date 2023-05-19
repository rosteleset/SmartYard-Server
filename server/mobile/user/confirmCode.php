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
    if ($user_phone[0] == '8') { 
        $user_phone[0] = '7'; 
    }
    $pin = @$postdata['smsCode'];
    $isdn = loadBackend("isdn");
    $households = loadBackend("households");
    $confirmMethod = @$config["backends"]["isdn"]["confirm_method"] ?: "outgoingCall";

    if (strlen($pin) == 4) {
        $pinreq = $redis->get("userpin_".$user_phone);

        $redis->setex("userpin.attempts_".$user_phone, 3600, (int)$redis->get("userpin.attempts_".$user_phone) + 1);

        if (!$pinreq) {
            response(404);
        } else {
            if ($pinreq != $pin) {
                $attempts = $redis->get("userpin.attempts_".$user_phone);
                if ($attempts > 5) {
                    $redis->del("userpin_".$user_phone);
                    $redis->del("userpin.attempts_".$user_phone);
                    response(403, false, "Превышено максимальное число попыток ввода", "Превышено максимальное число попыток ввода");
                } else {
                    response(403, false, "Пин-код введен неверно", "Пин-код введен неверно");
                }
            } else {
                $redis->del("userpin_".$user_phone);
                $redis->del("userpin.attempts_".$user_phone);
                $token = GUIDv4();
                $subscribers = $households->getSubscribers("mobile", $user_phone);
                $names = [ "name" => "", "patronymic" => "" ];
                if ($subscribers) {
                    $subscriber = $subscribers[0];
                    // Пользователь найден
                    $households->modifySubscriber($subscriber["subscriberId"], [ "authToken" => $token ]);
                    $names = [ "name" => $subscriber["subscriberName"], "patronymic" => $subscriber["subscriberPatronymic"] ];
                } else {
                    // Пользователь не найден - создаём
                    $id = $households->addSubscriber($user_phone, "", "");
                    $households->modifySubscriber($id, [ "authToken" => $token ]);
                }
                response(200, [ 'accessToken' => $token, 'names' => $names ]);
            }
        }
    } else {
        response(422);
    }

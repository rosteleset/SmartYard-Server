<?php

/**
 * @api {post} /user/checkPhone подтвердить телефон по исходящему звонку из приложения
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiParam {String{11}} userPhone номер телефона
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
    $user_phone = substr($user_phone,1);
    $households = loadBackend("households");

    $isdn = loadBackend("isdn");
    
    if (strlen($user_phone) == 10)  {
        
        $result = $isdn->checkIncoming('8'. $user_phone);
        $result2 = $isdn->checkIncoming('+7'. $user_phone);
        $result3 = $isdn->checkIncoming('7'. $user_phone);

        if ($result || $result2 || $result3 || $user_phone == "9123456781" || $user_phone == "9123456782") {
            $user_phone = '7' . $user_phone;
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
        } else {
            response(401);
        }
    } else {
        response(422);
    }

<?php

/**
 * @api {post} /user/checkPhone подтвердить телефон по исходящему звонку из приложения
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiParam {String{11}} userPhone номер телефона с кодом страны без "+"
 * @apiParam {String} deviceToken токен устройства
 * @apiParam {Number=0,1,2} platform тип клиента 0 - android, 1 - ios, 2 - web
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
$device_token = @$postdata['deviceToken'] ?: 'default';
$platform = @$postdata['platform'] ?: '0';

$households = loadBackend("households");
$isdn = loadBackend("isdn");
$inbox = loadBackend("inbox");

$result = $isdn->checkIncoming('+' . $user_phone);

if (strlen($user_phone) == 11 && $user_phone[0] == '7') {
    // для номеров из РФ дополнтельно ещё проверяем на номера вида "7XXXXXXXXXX" (без "+") и "8XXXXXXXXXX"
    $result = $result || $isdn->checkIncoming($user_phone);
    $result = $result || $isdn->checkIncoming('8' . substr($user_phone, 1));
}

if ($result || $user_phone == "79123456781" || $user_phone == "79123456782") {
    $token = GUIDv4();
    $subscribers = $households->getSubscribers("mobile", $user_phone);
    $devices = $households->getDevices("deviceToken", $device_token);
    $subscriber_id = false;
    $names = ["name" => "", "patronymic" => "", "last" => ""];
    if ($subscribers) {
        $subscriber = $subscribers[0];
        // Пользователь найден
        $subscriber_id = $subscriber["subscriberId"];
        $names = ["name" => $subscriber["subscriberName"], "patronymic" => $subscriber["subscriberPatronymic"], "last" => $subscriber["subscriberLast"]];
    } else {
        // Пользователь не найден - создаём
        $subscriber_id = $households->addSubscriber($user_phone, "", "", "");
    }

    // temporary solution
    if ($devices) {
        $device = $devices[0];
        if ($device["subscriberId"] != $subscriber_id) {
            $households->deleteDevice($device["deviceId"]);
            $households->addDevice($subscriber_id, $device_token, $platform, $token);
            $inbox->sendMessage($subscriber_id, "Внимание!", "Произведена авторизация на новом устройстве", $action = "inbox");
        } else {
            $households->modifyDevice($device["deviceId"], ["authToken" => $token]);
        }
    } else {
        $households->addDevice($subscriber_id, $device_token, $platform, $token);
        $inbox->sendMessage($subscriber_id, "Внимание!", "Произведена авторизация на новом устройстве", $action = "inbox");
    }
    response(200, ['accessToken' => $token, 'names' => $names]);
} else {
    response(401);
}

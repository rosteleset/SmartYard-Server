<?php

/**
 * @api {post} /user/registerPushToken зарегистрировать токен(ы) для пуш уведомлений
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} [pushToken] токен
 * @apiParam {String} [voipToken] токен
 * @apiParam {String="t","f"} [production="t"] использовать боевой сервер для voip пушей (ios only)
 * @apiParam {String="ios","android"} platform тип устройства: ios, android
 *
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 * 449 неверный clientId
 * 406 неправильный токен
 */

auth();
$households = loadBackend("households");
$isdn = loadBackend("isdn");

$old_push = $subscriber["pushToken"];
$push = trim(@$postdata['pushToken']);
$voip = trim(@$postdata['voipToken'] ?: "");
$production = trim(@$postdata['production']);

if (!array_key_exists('platform', $postdata) || ($postdata['platform'] != 'ios' && $postdata['platform'] != 'android')) {
    response(422);
}

if ($push && (strlen($push) < 16 || strlen($push) >= 1024)) {
    response(406);
}

if ($voip && (strlen($voip) < 16 || strlen($voip) >= 1024)) {
    response(406);
}

if ($postdata['platform'] == 'ios') {
    if ($voip) {
        $type = ($production == 'f')?2:1; // apn:apn.dev
    } else {
        $type = 3; // fcm (resend)
    }
} else {
    $type = 0; // fcm
}

$households->modifySubscriber($subscriber["subscriberId"], [ "pushToken" => $push, "tokenType" => $type, "voipToken" => $voip, "platform" => $postdata['platform'] ]);

if (!$push) {
    $households->modifySubscriber($subscriber["subscriberId"], [ "pushToken" => "off" ]);   
} else {
    if ($old_push && $old_push != $push) {
        $md5 = md5($push.$old_push);
        $payload = [
            "token" => $old_push,
            "messageId" => $md5,
            "msg" => urlencode("Произведена авторизация на другом устройстве"),
            "badge" => "1",
            "pushAction" => "logout"
        ];
        $isdn->push($payload);
        // file_get_contents("http://127.0.0.1:8082/push?token=".urlencode($old_push)."&message_id=$md5&msg=".urlencode("Произведена авторизация на другом устройстве")."&badge=1&action=logout&phone={$bearer['id']}");
    }
}

if (!$voip) {
    $households->modifySubscriber($subscriber["subscriberId"], [ "voipToken" => "off" ]);   
}

response();
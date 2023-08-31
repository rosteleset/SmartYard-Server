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

$user = auth();

$households = loadBackend("households");
$isdn = loadBackend("isdn");

$old_push = $user["pushToken"];

$push = trim(@$postdata['pushToken']);
$voip = trim(@$postdata['voipToken'] ?: "");

$production = trim(@$postdata['production']);

if (!array_key_exists('platform', $postdata) || ($postdata['platform'] != 'ios' && $postdata['platform'] != 'android' && $postdata['platform'] != 'huawei'))
    response(422);

if ($push && (strlen($push) < 16 || strlen($push) >= 1024))
    response(406);

if ($voip && (strlen($voip) < 16 || strlen($voip) >= 1024))
    response(406);

if ($postdata['platform'] == 'ios') {
    $platform = 1;
    if ($voip) {
        $type = ($production == 'f') ? 2 : 1; // apn:apn.dev
    } else {
        $type = 0; // fcm (resend)
    }
} elseif ($postdata['platform'] == 'huawei') {
    $platform = 0;
    $type = 3; // huawei
} else {
    $platform = 0;
    $type = 0; // fcm
}

$households->modifySubscriber($user["subscriberId"], ["pushToken" => $push, "tokenType" => $type, "voipToken" => $voip, "platform" => $platform]);

if (!$push)
    $households->modifySubscriber($user["subscriberId"], ["pushToken" => "off"]);
else {
    if ($old_push && $old_push != $push) {
        $md5 = md5($push . $old_push);
        $payload = [
            "token" => $old_push,
            "messageId" => $md5,
            "msg" => urlencode("Произведена авторизация на другом устройстве"),
            "badge" => "1",
            "pushAction" => "logout"
        ];

        $isdn->push($payload);
    }
}

if (!$voip)
    $households->modifySubscriber($user["subscriberId"], ["voipToken" => "off"]);

response();
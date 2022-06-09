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

$old_push = pg_fetch_result(pg_query("select push from domophones.bearers where id='{$bearer['id']}'"), 0);

$push = pg_escape_string(trim(@$postdata['pushToken']));
$voip = pg_escape_string(trim(@$postdata['voipToken']));
$production = pg_escape_string(trim(@$postdata['production']));

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

pg_query("update domophones.bearers set push='$push', voip='$voip', push_type='$type', device_type='{$postdata['platform']}' where id='{$bearer['id']}'");

if (!$push) {
    pg_query("update domophones.bearers set push='off' where id='{$bearer['id']}'");
} else {
    if ($old_push && $old_push != $push) {
        $md5 = md5($push.$old_push);
        file_get_contents("http://127.0.0.1:8082/push?token=".urlencode($old_push)."&message_id=$md5&msg=".urlencode("Произведена авторизация на другом устройстве")."&badge=1&action=logout&phone={$bearer['id']}");
    }
}

if (!$voip) {
    pg_query("update domophones.bearers set voip='off' where id='{$bearer['id']}'");
}

response();

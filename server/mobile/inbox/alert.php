<?php

/**
 * @api {post} /inbox/alert отправить сообщение самому себе
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} msg сообщение
 * @apiParam {String} action действие
 * @apiParam {String="t","f"} [pushOnly="t"] недублировать отправку через SMS
 */

auth();

$msg = @$postdata['msg'];
if (!$msg) {
    response(406, false, 'Ошибка', 'Сообщение (msg) не должно быть пустым');
}

$action = @$postdata['action'];
if (!$action) {
    response(406, false, 'Ошибка', 'Действие (action) не должно быть пустым');
}

//TODO: сделать обработку pushOnly

$inbox = loadBackend("inbox");
$subscriber_id = (int)$subscriber['subscriberId'];
$inbox->sendMessage($subscriber_id, '', $msg, $action);
response();

/*
$msg = trim(mysqli_escape_string($mysql, @$postdata['msg']));
$action = trim(mysqli_escape_string($mysql, @$postdata['action']));
$id = $bearer['id'];
$id[0] = '7';

if (!$id) {
    response($msg);
}

if (!$action) {
    $action = 'inbox';
}

$p = (@$postdata['pushOnly'] == 'f')?'false':'true';

mysql("insert into dm.inbox (date, id, msg, ext_id, push_only, action) values (now(), '$id', '$msg', md5(now()+rand()), $p, '$action')");

response();
*/

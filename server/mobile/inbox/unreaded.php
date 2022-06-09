<?php

/**
 * @api {post} /inbox/unreaded количество непрочитанных сообщений
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object} - объект
 * @apiSuccess {Number} -.count количество непрочитанных сообщений
 * @apiSuccess {Number=0,1} -.chat наличие непрочитанных сообщений в чате
 */

auth();

$id = $bearer['id'];
$id[0] = '7';

$unreaded = @(int)mysqli_fetch_assoc(mysql("select count(*) as c from dm.inbox where id='$id' and not readed"))['c'];
$chat = @(int)mysqli_fetch_assoc(mysql("select chat as c from dm.tokens where id='$id'"))['c'];

response(200, [
    'count' => $unreaded,
    'chat' => $chat,
]);

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

// TODO: получить число непрочитанных сообщений в чате
$chat = 0;

$inbox = loadBackend("inbox");
$subscriber_id = (int)$subscriber['subscriberId'];
$count_unread = (int)$inbox->unreaded($subscriber_id);
response(200, [
    'count' => $count_unread,
    'chat' => $chat,
]);

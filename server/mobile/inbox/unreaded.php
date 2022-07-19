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

// TODO: получить число непрочитанных сообщений в чате и в сообщениях
$unreaded = 0;
$chat = 0;

response(200, [
    'count' => $unreaded,
    'chat' => $chat,
]);

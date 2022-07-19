<?php

/**
 * @api {post} /inbox/readed отметить сообщение (все сообщения) как прочитанное
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} [messageId] идентификатор сообщения
 */

auth();

$id = @$postdata['messageId'];

if (!$id) {
    // TODO: отметить все сообщения как прочитанные
} else {
    // TODO: отметить сообщение как прочитанное
}

response();

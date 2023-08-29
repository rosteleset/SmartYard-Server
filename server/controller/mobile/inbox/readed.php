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

$msg_id = @$postdata['messageId'];

$inbox = loadBackend("inbox");
$subscriber_id = (int)$subscriber['subscriberId'];
if ($msg_id) {
    $inbox->markMessageAsReaded($subscriber_id, $msg_id);
} else {
    $inbox->markMessageAsReaded($subscriber_id);
}
response();

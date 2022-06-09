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

$id = mysqli_escape_string($mysql, @$postdata['messageId']);

if (!$id) {
    $id = $bearer['id'];
    $id[0] = '7';
    mysql("update dm.inbox set readed=true, code='app' where id='$id' and code is null");
    mysql("update dm.inbox set readed=true where id='$id'");
} else {
    mysql("update dm.inbox set readed=true, code='app' where ext_id='$id' and code is null");
    mysql("update dm.inbox set readed=true where ext_id='$id'");
}

response();

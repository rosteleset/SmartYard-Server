<?php

/**
 * @api {post} /inbox/delivered отметить сообщение как доставленое
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} messageId идентификатор сообщения
 */

auth();

$id = mysqli_escape_string($mysql, @$postdata['messageId']);

if (!$id) {
    response(422);
}

mysql("update dm.inbox set delivered=true where ext_id='$id'");
response();

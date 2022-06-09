<?php

/**
 * @api {post} /inbox/chatReaded отметить что все сообщения в чате доставлены (прочитаны)
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup Inbox
 *
 * @apiHeader {String} authorization токен авторизации
 */

auth();

$id = $bearer['id'];
$id[0] = '7';

mysql("update dm.tokens set chat=0 where id='$id'");

response();

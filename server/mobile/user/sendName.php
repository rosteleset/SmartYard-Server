<?php

/**
 * @api {post} /user/sendName установить "обращение"
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} name имя
 * @apiParam {String} [patronymic] отчество
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 * 406 неверный тип токена
 * 400 имя не указано
 */

auth();

$name = pg_escape_string(htmlspecialchars(trim(@$postdata['name'])));
$patronymic = pg_escape_string(htmlspecialchars(trim(@$postdata['patronymic'])));

if (!$name) {
    response(400);
}

@pg_query("insert into client_phone_names (phone) values ('{$bearer['id']}')");
if ($patronymic) {
    pg_query("update client_phone_names set name='$name', mname='$patronymic' where phone='{$bearer['id']}'");
} else {
    pg_query("update client_phone_names set name='$name', mname=null where phone='{$bearer['id']}'");
}

response();

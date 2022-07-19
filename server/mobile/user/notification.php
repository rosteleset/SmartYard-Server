<?php

/**
 * @api {post} /user/notification управление уведомлениями
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String="t","f"} [money] присылать сообщения "необходимо пополнить баланс (31<sup>*</sup>,1,3,10)"
 * @apiParam {String="t","f"} [enable] разрешить входящие текстовые сообщения
 *
 * @apiSuccess {String="t","f"} money присылать сообщения "необходимо пополнить баланс (31<sup>*</sup>,1,3,10)"
 * @apiSuccess {String="t","f"} enable разрешить входящие текстовые сообщения
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

auth();

$money = true;
$enable = true;

// TODO: сделать управление уведомлениями
if (@$postdata['money'] == 't' && !$money) {
    // pg_query("delete from domophones.money_disable where id='$phone'");
    $money = 1;
}

if (@$postdata['money'] == 'f' && $money) {
    // pg_query("insert into domophones.money_disable (id) values ('$phone')");
    $money = 0;
}

if (@$postdata['enable'] == 't' && !$enable) {
    // pg_query("delete from domophones.push_disable where id='$phone'");
    $enable = 1;
}

if (@$postdata['enable'] == 'f' && $enable) {
    // pg_query("insert into domophones.push_disable (id) values ('$phone')");
    $enable = 0;
}

response(200, [ "money" => $money?"t":"f", "enable" => $enable?"t":"f" ]);

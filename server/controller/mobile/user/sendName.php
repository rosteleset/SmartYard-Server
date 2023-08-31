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

$user = auth();

$name = htmlspecialchars(trim(@$postdata['name']));
$patronymic = htmlspecialchars(trim(@$postdata['patronymic']));

$households = loadBackend("households");

if (!$name) response(400);

if ($user) {
    if ($patronymic) $households->modifySubscriber($user["subscriberId"], ["subscriberName" => $name, "subscriberPatronymic" => $patronymic]);
    else $households->modifySubscriber($user["subscriberId"], ["subscriberName" => $name]);

    response();
} else response(400);
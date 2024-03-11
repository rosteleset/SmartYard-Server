<?php

/**
 * @api {post} /user/getName получить "обращение"
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} names имя и отчество
 * @apiSuccess {String} names.name имя
 * @apiSuccess {String} names.patronymic отчество
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 * 406 неверный тип токена
 */

auth();

$households = loadBackend("households");


if ($subscriber) {
    $names = ["name" => $subscriber["subscriberName"], "patronymic" => $subscriber["subscriberPatronymic"]];

    response(200, ['names' => $names]);
} else {
    response(404);
}

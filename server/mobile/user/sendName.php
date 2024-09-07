<?php

    /**
     * @api {post} /mobile/user/sendName установить "обращение"
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} [last] фамилия
     * @apiBody {String} name имя
     * @apiBody {String} [patronymic] отчество
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

    $last = htmlspecialchars(trim(@$postdata['last']));
    $name = htmlspecialchars(trim(@$postdata['name']));
    $patronymic = htmlspecialchars(trim(@$postdata['patronymic']));

    $households = loadBackend("households");

    if (!$name) {
        response(400);
    }

    if ($subscriber) {
        $full_name = [];
        if ($last) {
            $full_name["subscriberLast"] = $last;
        }
        if ($patronymic) {
            $full_name["subscriberPatronymic"] = $patronymic;
        }
        $full_name["subscriberName"] = $name;
        $households->modifySubscriber($subscriber["subscriberId"], $full_name);
        response();
    } else {
        response(400);
    }

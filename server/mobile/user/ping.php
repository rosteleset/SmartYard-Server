<?php

    /**
     * @api {post} /mobile/user/ping проверка доступности сервиса
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiErrorExample Ошибки
     * 403 отсутсвует HTTP_AUTHORIZATION
     * 422 отсутсвует Bearer
     * 401 токена нет в базе
     */


    auth();

    response();

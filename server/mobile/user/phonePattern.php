<?php

    /**
     * @api {post} /mobile/user/phonePattern определяет формат телефонного номера пользователя
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     */

    response(200, @$config["mobile"]["phone_pattern"] ? : "");
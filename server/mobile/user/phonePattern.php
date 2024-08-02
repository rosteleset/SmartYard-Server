<?php

    /**
     * @api {post} /user/phonePattern определяет формат телефонного номера пользователя
     * @apiVersion 1.0.0
     * @apiDescription ***готово***
     *
     * @apiGroup User
     *
     */

    error_log(print_r(apache_request_headers(), true));

    response(200, @$config["mobile"]["phonePattern"] ?: "");
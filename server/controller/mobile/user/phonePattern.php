<?php

/**
 * @api {post} /user/phonePattern определяет формат телефонного номера пользователя
 * @apiVersion 1.0.0
 * @apiDescription ***готово***
 *
 * @apiGroup User
 *
 */

response(200, @$config["mobile"]["phonePattern"] ?: "");
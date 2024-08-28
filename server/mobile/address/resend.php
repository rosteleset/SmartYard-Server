<?php

/**
 * @api {post} /address/resend повторная отправка информации для гостя
 * @apiVersion 1.0.0
 * @apiDescription **должен работать**
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} flatId идентификатор квартиры
 * @apiParam {String{11}} guestPhone номер телефона
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

    auth();
    response();

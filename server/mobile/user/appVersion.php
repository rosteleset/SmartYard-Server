<?php

/**
 * @api {post} /user/appVersion сообщить версию приложения
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} version версия (build) приложения
 * @apiParam {String="ios","android"} platform тип устройства: ios, android
 *
 * @apiSuccess {String="none","upgrade","force_upgrade"} [-="none"] требуемое действие
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 * 449 неверный clientId
 * 406 неправильный токен
 */

    auth();
    // TODO: сделать управление обновлением приложения и сбор статистики о версии.
    response();

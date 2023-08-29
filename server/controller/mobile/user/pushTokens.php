<?php

/**
 * @api {post} /user/pushTokens получить пуш токены для проверки
 * @apiVersion 1.0.0
 * @apiDescription **[метод готов]**
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String=token-data,"off","err",null} pushToken токен сообщений
 * @apiSuccess {String=token-data,"off","err",null} voipToken VoIP токен
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
response(200, [
    "pushToken" => $subscriber['pushToken'],
    "voipToken" => $subscriber['voipToken'],
    ]);

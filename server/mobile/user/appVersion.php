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
 * @apiSuccess {String="none","upgrade","forceUpgrade"} [-="none"] требуемое действие
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

$version = (int)@$postdata['version'];

if (!$version) {
    response(422);
}

if (!array_key_exists('platform', $postdata) || ($postdata['platform'] != 'ios' && $postdata['platform'] != 'android')) {
    response(422);
}

pg_query("update domophones.bearers set version=$version, device_type='{$postdata['platform']}' where id='{$bearer['id']}'");

$v = pg_fetch_assoc(pg_query("select upgrade, force_upgrade from domophones.versions where platform='{$postdata['platform']}'"));

if (!$v) {
    response();
}

if ($version < (int)$v['force_upgrade']) {
    response(200, 'forceUpgrade');
}

if ($version < $v['upgrade']) {
    response(200, 'upgrade');
}

response();

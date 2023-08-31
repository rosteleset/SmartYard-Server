<?php

/**
 * @api {post} /address/plogDays получить список дат (дней) на которые есть записи в журнале событий объекта
 * @apiVersion 1.0.0
 * @apiDescription ***в работе***
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} flatId идентификатор квартиры
 * @apiParam {String} [events] фильтр типов событий (через запятую)
 *
 * @apiSuccess {Object[]} - массив объектов
 * @apiSuccess {String="Y-m-d"} -.day дата (день)
 * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
 * @apiSuccess {Number} -.events количество событий
 *
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

$user = auth();

$households = loadBackend("households");
$flat_id = (int)@$postdata['flatId'];

if (!$flat_id)
    response(422);

$flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
$f = in_array($flat_id, $flat_ids);

if (!$f)
    response(404);

$plog = loadBackend("plog");
if (!$plog) response(403);

$flat_owner = false;
foreach ($user['flats'] as $flat)
    if ($flat['flatId'] == $flat_id) {
        $flat_owner = ($flat['role'] == 0);

        break;
    }

$flat_details = $households->getFlat($flat_id);
$plog_access = $flat_details['plog'];

if ($plog_access == $plog::ACCESS_DENIED || $plog_access == $plog::ACCESS_RESTRICTED_BY_ADMIN || $plog_access == $plog::ACCESS_OWNER_ONLY && !$flat_owner)
    response(403);

$filter_events = false;

if (@$postdata['events']) {
    $filter_events = explode(',', $postdata['events']);
    $t = [];

    foreach ($filter_events as $e)
        $t[(int)$e] = 1;

    $filter_events = [];

    foreach ($t as $e => $one)
        $filter_events[] = $e;

    $filter_events = implode(',', $filter_events);
}

try {
    $result = $plog->getEventsDays($flat_id, $filter_events);

    if ($result) response(200, $result);
    else response();
} catch (Throwable $e) {
    response(500, false, 'Внутренняя ошибка сервера');
}
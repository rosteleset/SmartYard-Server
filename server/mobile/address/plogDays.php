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

auth();
$households = loadBackend("households");
$flat_id = (int)@$postdata['flatId'];

if (!$flat_id) {
    response(422);
}

$flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
$f = in_array($flat_id, $flatIds);
if (!$f) {
    response(404);
}

$plog = loadBackend("plog");
if (!$plog) {
    response(403);
}

//проверка на доступность событий
$flat_owner = false;
foreach ($subscriber['flats'] as $flat) {
    if ($flat['flatId'] == $flat_id) {
        $flat_owner = ($flat['role'] == 0);
        break;
    }
}

$flat_details = $households->getFlat($flat_id);
$plog_access = $flat_details['plog'];
if ($plog_access == $plog::ACCESS_DENIED || $plog_access == $plog::ACCESS_RESTRICTED_BY_ADMIN
    || $plog_access == $plog::ACCESS_OWNER_ONLY && !$flat_owner) {
    response(403);
}

$filter_events = false;
if (@$postdata['events']) {
    //фильтр событий

    $filter_events = explode(',', $postdata['events']);
    $t = [];
    foreach ($filter_events as $e) {
        $t[(int)$e] = 1;
    }
    $filter_events = [];
    foreach ($t as $e => $one) {
        $filter_events[] = $e;
    }
    $filter_events = implode(',', $filter_events);
}

try {
    $result = $plog->getEventsDays($flat_id, $filter_events);
    if ($result) {
        response(200, $result);
    } else {
        response();
    }
} catch (\Throwable $e)  {
    response(500, false, 'Внутренняя ошибка сервера');
}

/*

    $flat_id = @(int)$postdata['flatId'];

    if (!in_array($flat_id, all_flats())) {
        response(404);
    }

    $f = pg_fetch_assoc(pg_query("select disable_plog, hidden_plog from domophones.flat_settings where flat_id = $flat_id"));
    $hidden = @$f['hidden_plog'] == 't';
    $disabled = @$f['disable_plog'] == 't';

    if ($disabled) {
        response(403);
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);
    if ($hidden && $my_relation_to_this_flat != 'owner') {
        response(403);
    }

    $events = false;

    if (@$postdata['events']) {
        $events = explode(',', $postdata['events']);
        $t = [];
        foreach ($events as $e) {
            $t[(int)$e] = 1;
        }
        $events = [];
        foreach ($t as $e => $one) {
            $events[] = $e;
        }
        $events = implode(',', $events);
    }

    if ($events) {
        $qr = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and object_type = 0 and event in ($events) group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
    } else {
        $qr = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and object_type = 0 group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
    }

    $resp = mysqli_fetch_all(clickhouse($qr), MYSQLI_ASSOC);

    if (count($resp)) {
        foreach ($resp as &$d) {
            $d['day'] = substr($d['day'], 0, 4) . '-' . substr($d['day'], 4, 2) . '-' . substr($d['day'], 6, 2);
        }
        response(200, $resp);
    } else {
        response();
    }
*/

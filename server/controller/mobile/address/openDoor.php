<?php

/**
 * @api {post} /address/openDoor открыть дверь (калитку, ворота, шлагбаум)
 * @apiVersion 1.0.0
 * @apiDescription ***нуждается в доработке***
 *
 * @apiGroup Address
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {Number} domophoneId идентификатор домофона
 * @apiParam {Number=0,1,2} [doorId=0] идентификатор двери (калитки, ворот, шлагбаума)
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

use Selpol\Service\DomophoneService;

$user = auth(15);

$domophone_id = (int)@$postdata['domophoneId'];
$door_id = (int)@$postdata['doorId'];

$households = backend("households");

// Check intercom is blocking
$blocked = true;

foreach ($user['flats'] as $flat) {
    $flatDetail = $households->getFlat($flat['flatId']);
    if ($flatDetail['autoBlock'] || $flatDetail['adminBlock'])
        continue;

    foreach ($flatDetail['entrances'] as $entrance) {
        $domophoneId = intval($entrance['domophoneId']);
        $e = $households->getEntrance($entrance['entranceId']);
        $doorId = intval($e['domophoneOutput']);

        if ($domophone_id == $domophoneId && $door_id == $doorId && !$flatDetail['manualBlock']) {
            $blocked = false;

            break;
        }
    }

    if ($blocked == false)
        break;
}

if (!$blocked) {
    $households = backend("households");
    $domophone = $households->getDomophone($domophone_id);

    try {
        $model = container(DomophoneService::class)->model($domophone["model"], $domophone["url"], $domophone["credentials"]);
        $model->open_door($door_id);

        $plog = backend("plog");

        if ($plog)
            $plog->addDoorOpenDataById(time(), $domophone_id, $plog::EVENT_OPENED_BY_APP, $door_id, $user['mobile']);
    } catch (Exception $e) {
        response(404, false, 'Ошибка', 'Домофон недоступен');
    }
    response();
} else {
    response(404, false, 'Не найдено', 'Услуга недоступна (договор заблокирован либо не оплачен)');
}
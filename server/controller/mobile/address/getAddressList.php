<?php

/**
 * @api {post} /address/getAddressList получить список адресов на главный экран
 * @apiVersion 1.0.0
 * @apiDescription **[не готов]**
 *
 * @apiGroup Address
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {integer} -.houseId идентификатор дома
 * @apiSuccess {string} -.address адрес
 * @apiSuccess {Object[]} [-.doors] замки домофонов
 * @apiSuccess {integer} -.doors.domophoneId идентификатор домофона
 * @apiSuccess {integer=0,1,2} -.doors.doorId идентификатор замка
 * @apiSuccess {integer} [-.doors.entrance] подъезд
 * @apiSuccess {string="entrance","wicket","gate","barrier"} -.doors.icon иконка замка
 * @apiSuccess {string} -.doors.name наименование замка
 * @apiSuccess {string} [-.doors.blocked] причина ограничения доступа к домофону
 * @apiSuccess {string} [-.doors.dst] номер открытия
 * @apiSuccess {string="t","f"} [-.hasPlog] доступность журнала событий
 * @apiSuccess {integer} -.cctv количество видеокамер
 * @apiSuccess {object[]} [-.ext] массив объектов
 * @apiSuccess {string} -.ext.caption имя расширения (для отображения)
 * @apiSuccess {string} -.ext.icon иконка расширения (svg)
 * @apiSuccess {string} -.ext.extId идентификатор расширения
 * @apiSuccess {string="t","f"} [-.ext.highlight="f"] "подсветка" (красная точка)
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 */

use backends\plog\plog;

$user = auth(3600);

$households = loadBackend("households");
$plog = loadBackend("plog");
$cameras = loadBackend("cameras");

$houses = [];

foreach ($user['flats'] as $flat) {
    $houseId = $flat['addressHouseId'];

    if (array_key_exists($houseId, $houses)) $house = &$houses[$houseId];
    else {
        $houses[$houseId] = [];
        $house = &$houses[$houseId];
        $house['houseId'] = strval($houseId);
        $house['address'] = $flat['house']['houseFull'];

        $is_owner = ((int)$flat['role'] == 0);
        $flat_plog = $households->getFlat($flat["flatId"])['plog'];
        $has_plog = $plog && ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY && $is_owner);

        if ($plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN)
            $house['hasPlog'] = $has_plog ? 't' : 'f';


        $house['cameras'] = $households->getCameras("houseId", $houseId);
        $house['doors'] = [];
    }

    if (array_key_exists('flats', $house)) $house['flats'][] = ['id' => $flat['flatId'], 'flat' => $flat['flat']];
    else $house['flats'] = [['id' => $flat['flatId'], 'flat' => $flat['flat']]];

    $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));
    $house['cctv'] = count($house['cameras']);

    $flatDetail = $households->getFlat($flat['flatId']);

    foreach ($flatDetail['entrances'] as $entrance) {
        if (array_key_exists($entrance['entranceId'], $house['doors']))
            continue;

        $e = $households->getEntrance($entrance['entranceId']);

        $door = [];
        $door['domophoneId'] = strval($e['domophoneId']);
        $door['doorId'] = intval($e['domophoneOutput']);
        $door['icon'] = $e['entranceType'];
        $door['name'] = $e['entrance'];

        if ($e['cameraId']) {
            $cam = $cameras->getCamera($e["cameraId"]);

            $house['cameras'][] = $cam;
            $house['cctv']++;
        }

        // TODO: проверить обработку блокировки
        //
        if ($flatDetail['autoBlock'] || $flatDetail['adminBlock'])
            $door['blocked'] = "Услуга домофонии заблокирована";

        $house['doors'][$entrance['entranceId']] = $door;
    }
}

// конвертируем ассоциативные массивы в простые и удаляем лишние ключи
foreach ($houses as $house_key => $h) {
    $houses[$house_key]['doors'] = array_values($h['doors']);

    unset($houses[$house_key]['cameras']);
}

$result = array_values($houses);

if (count($result)) response(200, $result);
else response();
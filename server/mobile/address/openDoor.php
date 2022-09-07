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

    auth(15);

    $domophone_id = (int)@$postdata['domophoneId'];
    $door_id = (int)@$postdata['doorId'];
    $households = loadBackend("households");
    
    // проверка на блокировку домофона
    $blocked = true;
    foreach($subscriber['flats'] as $flat) {
        $flatDetail = $households->getFlat($flat['flatId']);
        if ($flatDetail['autoBlock']) {
            continue;
        }

        foreach ($flatDetail['entrances'] as $entrance) {
            $domophoneId = intval($entrance['domophoneId']);
            $e = $households->getEntrance($entrance['entranceId']);
            $doorId = intval($e['domophoneOutput']);
            
            if($domophone_id == $domophoneId && $door_id == $doorId) {
                $blocked = false;
                break;
            }
        }

        if ($blocked == false) {
            break;
        }
    }

    if (!$blocked) {
        $households = loadBackend("households");
        $domophone = $households->getDomophone($domophone_id);

        try {
            $model = loadDomophone($domophone["model"], $domophone["url"], $domophone["credentials"]);
            $model->open_door($door_id);
        }
        catch (\Exception $e) {
            response(404, false, 'Ошибка', 'Домофон недоступен');
        }
        response();
    } else {
        response(404, false, 'Не найдено', 'Услуга недоступна (договор заблокирован либо не оплачен)');
    }
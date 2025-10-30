<?php

    /**
     * @api {post} /mobile/address/getAddressList получить список адресов на главный экран
     * @apiVersion 1.0.0
     * @apiDescription **не готов**
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
     * @apiSuccess {string} [-.doors.dst] номер открытия по звонку (пока не используется)
     * @apiSuccess {string} [-.doors.doorCode] код открытия двери (если нет значит выключено)
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

    auth();

    $households = loadBackend("households");
    $plog = loadBackend("plog");
    $cameras = loadBackend("cameras");

    $houses = [];

    foreach ($subscriber['flats'] as $flat) {
        $houseId = $flat['addressHouseId'];

        if (array_key_exists($houseId, $houses)) {
            $house = &$houses[$houseId];
        } else {
            $houses[$houseId] = [];
            $house = &$houses[$houseId];
            $house['houseId'] = strval($houseId);
            $house['address'] = $flat['house']['houseFull'];
            $is_owner = ((int)$flat['role'] == 0);
            $flat_plog = $households->getFlat($flat["flatId"])['plog'];
            $has_plog = $plog && ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY && $is_owner);
            if ($plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
                $house['hasPlog'] = $has_plog ? 't' : 'f';
            }
            $house['cameras'] = $households->getCameras("houseId", $houseId);
            $house['doors'] = [];
        }

        $house['cameras'] = array_merge($house['cameras'], $households->getCameras("flatId", $flat['flatId']));

        $flatDetail = $households->getFlat($flat['flatId']);
        foreach ($flatDetail['entrances'] as $entrance) {
            if (array_key_exists($entrance['entranceId'], $house['doors'])) {
                if (isset($flatDetail['openCode']) && $flatDetail['openCode'] != '00000' && !isset($house['doors'][$entrance['entranceId']]['doorCode'])) {
                    $house['doors'][$entrance['entranceId']]['doorCode'] = $flatDetail['openCode'];
                }

                continue;
            }

            $e = $households->getEntrance($entrance['entranceId']);
            $door = [];
            $door['domophoneId'] = strval($e['domophoneId']);
            $door['doorId'] = intval($e['domophoneOutput']);
            $door['icon'] = $e['entranceType'];
            $door['name'] = $e['entrance'];
            if (!empty($flatDetail['openCode']) && $flatDetail['openCode'] != '00000') {
                $door['doorCode'] = $flatDetail['openCode'];
            }

            if ($e['cameraId']) {
                $cam = $cameras->getCamera($e["cameraId"]);
                $house['cameras'][] = $cam;
            }

            // TODO: проверить обработку блокировки
            //
            if ($flatDetail['autoBlock'] || $flatDetail['adminBlock']) {
                $door['blocked'] = i18n("mobile.blocked");
            }

            $house['doors'][$entrance['entranceId']] = $door;
        }
    }

    // конвертируем ассоциативные массивы в простые и удаляем лишние ключи
    foreach ($houses as $house_key => $h) {
        // count unique cameras
        $tempArr = array_unique(array_column($h['cameras'], 'cameraId'));
        $houses[$house_key]['cctv'] = count($tempArr);

        $doors = array_values($h['doors']);
        usort($doors, function ($a, $b) {
            $entrance_type_order = [
                "entrance" => 0,
                "wicket" => 1,
                "gate" => 2,
                "barrier" => 3,
            ];
            if ($entrance_type_order[$a['icon']] > $entrance_type_order[$b['icon']]) {
                return 1;
            }
            if ($entrance_type_order[$a['icon']] < $entrance_type_order[$b['icon']]) {
                return -1;
            }
            return $a['name'] > $b['name'];
        });
        $houses[$house_key]['doors'] = $doors;
        unset($houses[$house_key]['cameras']);
    }
    $result = array_values($houses);

    if (count($result)) {
        response(200, $result);
    } else {
        response();
    }

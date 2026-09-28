<?php

    /**
     * @api {post} /mobile/address/getSettingsList получить список адресов для настроек
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup Address
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiSuccess {object[]} - массив объектов
     * @apiSuccess {string} [-.clientId] идентификатор клиента
     * @apiSuccess {string} [-.clientName] имя абонента
     * @apiSuccess {string} [-.contractName] номер договора
     * @apiSuccess {string="t","f"} [-.flatOwner] владелец квартиры
     * @apiSuccess {string="t","f"} [-.contractOwner] владелец договора
     * @apiSuccess {string="t","f"} [-.hasGates] есть ворота и (или) шлагбаумы
     * @apiSuccess {string} [-.houseId] идентификатор дома
     * @apiSuccess {string} [-.flatId] идентификатор квартиры
     * @apiSuccess {string} [-.flatNumber] номер квартиры
     * @apiSuccess {string} [-.doorCode] код открытия двери (если нет значит выключено)
     * @apiSuccess {string="t","f"} [-.hasPlog] доступность журнала событий
     * @apiSuccess {string} -.address адрес
     * @apiSuccess {string[]="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.services подключенные услуги
     * @apiSuccess {string} [-.lcab] личный кабинет
     * @apiSuccess {object[]} [-.roommates] сокамерники
     * @apiSuccess {string} -.roommates.phone телефон
     * @apiSuccess {integer} [-.roommates.timezone] часовой пояс (default - Moscow Time)
     * @apiSuccess {string="Y-m-d H:i:s"} -.roommates.expire дата до которой действует доступ
     * @apiSuccess {string="inner","outer","owner"} -.roommates.type тип inner - доступ к домофону, outer - только калитки и ворота, owner - владелец
     * @apiSuccess {Object[]} [-.availableServices] массив доступных услуг
     * @apiSuccess {String="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.availableServices.icon иконка услуги
     * @apiSuccess {String} -.availableServices.title заголовок
     * @apiSuccess {String} -.availableServices.description описание
     * @apiSuccess {String="t","f"} -.availableServices.canChange доступна смена тарифа
     * @apiSuccess {String="t","f"} -.availableServices.byDefault услуга предоставляется по умолчанию
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
    $flats = [];

    foreach ($subscriber['flats'] as $flat) {
        $f = [];

        $h_flat = $households->getFlat($flat["flatId"]);

        $f['address'] = $flat['house']['houseFull'] . ', кв. ' . $flat['flat'];
        $f['houseId'] = strval($flat['house']['houseId']);
        $f['flatId'] = strval($flat['flatId']);
        $f['flatNumber'] = strval($flat['flat']);
        if (isset($h_flat['openCode']) && $h_flat['openCode'] != '00000') {
            $f['doorCode'] = $h_flat['openCode'];
        }
        $is_owner = ((int)$flat['role'] == 0);
        $f['flatOwner'] = $is_owner ? 't' : 'f';

        // TODO : сделать временный доступ к воротам. пока он отключен, и в приложении этот раздел просто не будет отображаться.
        $f['hasGates'] = 'f';

        $flat_plog = $h_flat['plog'];
        $has_plog = $plog && ($flat_plog == plog::ACCESS_ALL || $flat_plog == plog::ACCESS_OWNER_ONLY && $is_owner);
        if ($plog && $flat_plog != plog::ACCESS_RESTRICTED_BY_ADMIN) {
            $f['hasPlog'] = $has_plog ? 't' : 'f';
        }

        // TODO: сделать работу с заявками на изменение услуг. пока блок выбора услуг - "тарелочки" отключены.
        // в услугах должна быть услуга domophone, чтобы было доступно управление доступом.
        // contractOwner = 'f' отключает отображение тарелочек.
        //$f['services'] = ['domophone'];

        // Flat services. Service "domophone" is determined by adminBlock, manualBlock, autoBlock and entrance count
        $flat_services = $households->getFlatServices($flat['flatId']);
        if (!isset($flat_services)) {
            $flat_services = [];
        }
        $has_entrances = is_array($h_flat['entrances']) && count($h_flat['entrances']) > 0;
        $domophone_service = "domophone";
        $has_domophone_service = !($h_flat['adminBlock'] || $h_flat['manualBlock'] || $h_flat['autoBlock']) && $has_entrances;
        if ($has_domophone_service) {
            if (!in_array($domophone_service, $flat_services, true)) {
                $flat_services[] = $domophone_service;
            }
        } else {
            $key = array_search($domophone_service, $flat_services, true);
            if ($key !== false) {
                unset($flat_services[$key]);
            }
        }
        $f['services'] = $flat_services;

        // Available services
        $house_services = $households->getHouseServices($h_flat['houseId']);
        if (isset($house_services)) {
            $f['availableServices'] = $house_services;
        }

        $f['contractOwner'] = (int)$flat['role']==0 ? 't' : 'f';
        //$f['contractOwner'] = 'f';
        // $f['contractName'] = '-';
        // $f['clientId'] = '0';

        $subscribers = $households->getSubscribers('flatId', $f['flatId'], [ "withoutHouses" ]);
        $rms = [];
        foreach ($subscribers as $s) {
            if ($subscriber['subscriberId'] == $s['subscriberId']) {
                continue;
            }
            $rm = [];
            $rm['phone'] = $s['mobile'];
            // $rm['phone'][0] = '7';
            $rm['expire'] = '3001-01-01 00:00:00';

            foreach ($s['flats'] as $sf) {
                if ($sf['flatId'] == $flat['flatId']) {
                    $rm['type'] = $sf['role'] == 0 ? 'owner' : 'inner';
                }
            }
            $rms[] = $rm;
        }
        $f['roommates'] = $rms;

        if ($is_owner) {
            $f["keys"] = $households->getKeys("flatId", $f['flatId']);
        }

        mobileCustomize('address.getSettingsList.flat', $f, [
            'subscriber' => $subscriber,
            'flat' => $flat,
            'flatDetail' => $h_flat,
            'isOwner' => $is_owner,
        ]);

        $flats[] = $f;
    }

    $result = $flats;

    if (count($result)) {
        response(200, $result);
    } else {
        response();
    }

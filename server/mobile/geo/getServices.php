<?php

    /**
     * @api {post} /mobile/geo/getServices список доступных услуг
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup Geo
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {Number} houseId дом
     *
     * @apiSuccess {Object[]} - массив объектов
     * @apiSuccess {String="internet","iptv","ctv","phone","cctv","domophone","gsm"} -.icon иконка услуги
     * @apiSuccess {String} -.title заголовок
     * @apiSuccess {String} -.description описание
     * @apiSuccess {String="t","f"} -.canChange доступна смена тарифа
     * @apiSuccess {String="t","f"} -.byDefault услуга предоставляется по умолчанию
     */

    auth();

    $house_id = (int)@$postdata['houseId'];

    if (!$house_id) {
        response(422);
    }
    $households = loadBackend("households");
    $customFields = loadBackend("customFields");

    $ret = [];
    $services = [];
    $allowedServices = [
        "internet",
        "iptv",
        "ctv",
        "phone",
        "cctv",
        "domophone",
        "gsm",
    ];

    if ($customFields) {
        $values = $customFields->getValues("house", $house_id);

        if (is_array($values)) {
            $servicesValue = trim((string)@$values["services"]);

            foreach (explode(",", $servicesValue) as $service) {
                $service = trim(mb_strtolower($service));

                if (in_array($service, $allowedServices, true) && isset($RBTServices[$service])) {
                    $services[$service] = $service;
                }
            }
        }
    }

    if ($households->getFlats('houseId', $house_id)) {
        if (!count($services)) {
            $services = [ "domophone" => "domophone" ];
        }

        foreach ($services as $service) {
            $s = $RBTServices[$service];
            $s['canChange'] = 't';
            $s['byDefault'] = $service === 'domophone' ? 't' : 'f';
            $ret[] = $s;
        }
    }


    if (count($ret)) {
        response(200, $ret);
    } else {
        response();
    }

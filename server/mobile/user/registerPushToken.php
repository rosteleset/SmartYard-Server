<?php

    /**
     * @api {post} /mobile/user/registerPushToken зарегистрировать токен(ы) для пуш уведомлений
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} [pushToken] токен
     * @apiBody {String} [voipToken] токен
     * @apiBody {String="t","f"} [production="t"] использовать боевой сервер для voip пушей (ios only)
     * @apiBody {String="ios","android","web"} platform тип устройства: ios, android, web
     * @apiBody {String="fcm","apn","hms","rustore"} pushService поставщик услуг отправки пушей
     * @apiBody {String} [bundle] имя пакета (бандл)
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

    $households = loadBackend("households");

    $push = trim(@$postdata['pushToken']);
    $voip = trim(@$postdata['voipToken'] ?: "");
    $production = trim(@$postdata['production']);
    $bundle = trim(@$postdata['bundle']);

    if (!array_key_exists('platform', $postdata) || ($postdata['platform'] != 'ios' && $postdata['platform'] != 'android' && $postdata['platform'] != 'web')) {
        response(422);
    }

    if ($push && (strlen($push) < 16 || strlen($push) >= 1024)) {
        response(406);
    }

    if ($voip && (strlen($voip) < 16 || strlen($voip) >= 1024)) {
        response(406);
    }

    // platform -- 0 - android, 1 - ios, 2 - web
    // type     -- 0 - fcm, 1 - apple, 2 - apple (dev), 3 - not used, 4 - huawei, 5 - rustore

    $platform = 0;
    $type = 0;

    switch ($postdata['platform']) {
        case "ios":
            $platform = 1;
            if ($voip) {
                $type = ($production == 'f') ? 2 : 1; // apn : apn.dev
            } else {
                $type = 0; // fcm (resend)
            }
            break;

        case "android":
            switch (@$postdata['pushService']) {
                case "fcm":
                    $platform = 0;
                    $type = 0;
                    break;

                case "hms":
                    $platform = 0;
                    $type = 4;
                    break;

                case "rustore":
                    $platform = 0;
                    $type = 5;
                    break;
            }
            break;

        case "web":
            $platform = 2;
            $type = 0;
            break;
    }

    $households->modifyDevice($device["deviceId"], [
        "pushToken" => $push ?: "off" ,
        "tokenType" => $type,
        "voipToken" => $voip ?: "off",
        "platform" => $platform,
        "bundle" => $bundle ?: "default",
    ]);

    if ($device["deviceToken"][0] == "*" && $push) {
        $households->dedupDevices($device["deviceToken"], $push);
    }

    response();
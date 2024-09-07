<?php

    /**
     * @api {post} /mobile/user/addMyPhone добавить свой телефон
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} login логин
     * @apiBody {String} password пароль
     * @apiBody {String} [comment] комментарий
     *
     * @apiErrorExample Ошибки
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     * 449 неверный clientId
     */

    auth();

    $login = trim(@$postdata['login']);
    if (!$login) {
        response(404);
    }

    $password = trim(@$postdata['password']);
    if (!$password) {
        response(404);
    }

    $households = loadBackend("households");
    $flats = $households->getFlats("credentials", [ "login" => $login, "password" => $password ]);
    if (!$flats) {
        response(400, i18n("mobile.404"), i18n("mobile.404Contract"));
    }

    $already_count = 0;
    foreach ($flats as $flat) {
        $flat_id = (int)$flat["flatId"];

        //проверка регистрации пользователя в квартире
        $already = false;
        foreach($subscriber['flats'] as $item) {
            if ((int)$item['flatId'] === $flat_id) {
                $already = true;
                break;
            }
        }
        if ($already) {
            ++$already_count;
            continue;
        }

        if ($households->addSubscriber($subscriber["mobile"], "", "", "", $flat_id,
            [
                'title' => i18n("mobile.newAddressTitle"),
                'msg' => i18n("mobile.newAddressBody"),
            ])) {
            $f_list = [];
            foreach ($subscriber['flats'] as $item) {
                $f_id = (int)$item['flatId'];
                $f_role = !$item['role'];
                $f_voip_enabled = $item['voipEnabled'];
                $f_list[$f_id] = [
                    "role" => $f_role,
                    "voipEnabled" => $f_voip_enabled
                ];
            }
            $f_list[$flat_id]["role"] = true;  //делаем пользователя владельцем квартиры
            $households->setSubscriberFlats($subscriber['subscriberId'], $f_list);
        }
    }

    if ($already_count > 0) {
        response(404, i18n("mobile.message"), i18n("mobile.addressAlreadyExists"));
    }

    response();

<?php

/**
 * @api {post} /ext/options получение конфига поставщика услуг для приложения
 * @apiVersion 1.0.0
 * @apiDescription **[метод в разработке]**
 *
 * @apiGroup Ext
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String="t","f"} [cityCams="f"] городские камеры
 * @apiSuccess {String="t","f"} [issues="f"] заявки
 * @apiSuccess {String="t","f"} [payments="f"] вкладка оплата
 * @apiSuccess {String} [paymentsUrl] URL платёжной системы (версия web-расширений 2). Если отсутствует, то будет нативная поддержка платежей через /user/getPaymentsList
 * @apiSuccess {String="t","f"} [chat="f"] вкладка чат
 * @apiSuccess {String} [chatUrl] URL страницы чата (версия web-расширений 2). Если отсутствует, то будет нативная поддержка meTalk с chatOptions
 * @apiSuccess {Object} [chatOptions] Опции для meTalk
 * @apiSuccess {String} chatOptions.id id чата meTalk
 * @apiSuccess {String} chatOptions.domain domain чата meTalk
 * @apiSuccess {String} chatOptions.token token чата meTalk
 * @apiSuccess {String} [supportPhone] номер телефона техподдержки
 * @apiSuccess {String} [timeZone = "Europe/Moscow"] Time Zone identifier
 * @apiSuccess {String="turnOnOnly","turnOnAndOff"} [guestAccess = "turnOnOnly"] Тип гостевого доступа.
 * @apiSuccess {Number} [version] Версия API
 *
 * @apiErrorExample Ошибки
 * 403 требуется авторизация
 * 422 неверный формат данных
 * 404 пользователь не найден
 * 410 авторизация отозвана
 * 424 неверный токен
 **/

    auth();

    // отвечает за отображение раздела оплаты и городских камер

    $response = [
        "cityCams" => @$config["mobile"]["city_cams"] ? "t" : "f",
        "payments" => @$config["mobile"]["payments"] ? "t" : "f",
        "chat" => @$config["mobile"]["chat"] ? "t" : "f"
        ];
        
        if (@$config["mobile"]["support_phone"]) {
            $response["supportPhone"] = $config["mobile"]["support_phone"];
        }

        if (@$config["mobile"]["payments_url"]) {
            $response["paymentsUrl"] = $config["mobile"]["payments_url"];
        }

        if (@$config["mobile"]["chat_url"]) {
            $response["chatUrl"] = $config["mobile"]["chat_url"];
        } else {
            if (@$config["mobile"]["talk_me_id"] && @$config["mobile"]["talk_me_domain"] && @$config["mobile"]["talk_me_token"]) {
                $response["chat"] = "t";
                $response["chatOptions"] = [
                    "id" => $config["mobile"]["talk_me_id"],
                    "domain" => $config["mobile"]["talk_me_domain"],
                    "token" => $config["mobile"]["talk_me_token"]
                ];
            } else {
                $response["chat"] = "f";
            }
        }

        if (@$config["mobile"]["time_zone"]) {
            $response["timeZone"] = $config["mobile"]["time_zone"];
        }

        if (@$config["mobile"]["guest_access"]) {
            $response["guestAccess"] = $config["mobile"]["guest_access"];
        }

        $response["version"] = @$config["mobile"]["version"] ?: 0;

    response(200, $response);

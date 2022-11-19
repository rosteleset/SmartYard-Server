<?php

/**
 * @api {post} /ext/options получение конфига поставщика услуг для приложения
 * @apiVersion 1.0.0
 * @apiDescription **[метод в разработке]**
 *
 * @apiGroup Ext
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiSuccess {string="t","f"} [cityCams="f"] городские камеры
 * @apiSuccess {string="t","f"} [issues="f"] заявки
 * @apiSuccess {string="t","f"} [payments="f"] оплата за услуги
 * @apiSuccess {string} [paymentsUrl] URL платёжной системы
 * @apiSuccess {string} [supportPhone] номер телефона техподдержки
 * @apiSuccess {string="t","f"} [chat="f"] чат talkMe
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
        "payments" => @$config["mobile"]["payments"] ? "t" : "f"
        ];
        
        if (@$config["mobile"]["support_phone"]) {
            $response["supportPhone"] = $config["mobile"]["support_phone"];
        }

        if (@$config["mobile"]["payments_url"]) {
            $response["paymentsUrl"] = $config["mobile"]["payments_url"];
        }

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
    response(200, $response);

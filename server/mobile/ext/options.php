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
        "cityCams" => "f",
        "payments" => "f",
        "paymentsUrl" => "https://your.url.of.payments.page", 
        "supportPhone" => "+7(4752)429999"
        ];

        if (@$config["mobile"]["talkMe_id"] && @$config["mobile"]["talkMe_domain"] && @$config["mobile"]["talkMe_token"]) {
            $response["chat"] = "t";
            $response["chatOptions"] = [
                "id" => $config["mobile"]["talkMe_id"],
                "domain" => $config["mobile"]["talkMe_domain"],
                "token" => $config["mobile"]["talkMe_token"]
            ];
        } else {
            $response["chat"] = "f";
        }
    response(200, $response);

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
    response(200, [
        "cityCams" => "f",
        "issues" => "f",
        "payments" => "f",
        "paymentsUrl" => "https://your.url.of.payments.page", 
        "supportPhone" => "+7(4752)429999"
    ]);

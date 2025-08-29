<?php

    /**
     * @api {post} /mobile/ext/options получение конфига поставщика услуг для приложения
     * @apiVersion 1.0.0
     * @apiDescription **метод в разработке**
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
     * @apiSuccess {String="list","tree","userDefined"} [cctvView="list"] How to show cameras in the mobile app. list(default) - cameras are shown on the map; tree - cameras are shown as a tree structure; userDefined - user has a switch in the common settings
     * @apiSuccess {String="addresses", "notifications", "chat", "pay", "menu"} [activeTab="addresses"] Active app's tab at start.
     * @apiSuccess {String} [validationNamePattern=""] Regex validation pattern for a name
     * @apiSuccess {String} [validationPatronymicPattern=""] Regex validation pattern for a patronymic
     * @apiSuccess {String} [validationLastPattern=""] Regex validation pattern for a surname
     * @apiSuccess {String="t","f"} [addressVerificationTabLayoutVisible="t"] Address Verification UI: visibility of the tab layout
     * @apiSuccess {String="t","f"} [addressVerificationTab1Visible="t"] Address Verification UI: visibility of the tab 1
     * @apiSuccess {String="t","f"} [addressVerificationTab2Visible="t"] Address Verification UI: visibility of the tab 2
     *
     * @apiErrorExample Ошибки
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     */

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

    if (@$config["mobile"]["cctv_view"]) {
        $response["cctvView"] = $config["mobile"]["cctv_view"];
    }

    if (@$config["mobile"]["active_tab"]) {
        $response["activeTab"] = $config["mobile"]["active_tab"];
    }

    if (@$config["mobile"]["issues_version"]) {
        $response["issuesVersion"] = $config["mobile"]["issues_version"];
    }

    // Regex validation patterns
    if (@$config["mobile"]["validation_name_pattern"]) {
        $response["validationNamePattern"] = $config["mobile"]["validation_name_pattern"];
    }

    if (@$config["mobile"]["validation_patronymic_pattern"]) {
        $response["validationPatronymicPattern"] = $config["mobile"]["validation_patronymic_pattern"];
    }

    if (@$config["mobile"]["validation_last_pattern"]) {
        $response["validationLastPattern"] = $config["mobile"]["validation_last_pattern"];
    }

    // Address Verification UI
    $response["addressVerificationTabLayoutVisible"] = ($config["mobile"]["address_verification_tab_layout_visible"] ?? true) ? "t" : "f";
    $response["addressVerificationTab1Visible"] = ($config["mobile"]["address_verification_tab_1_visible"] ?? true) ? "t" : "f";
    $response["addressVerificationTab2Visible"] = ($config["mobile"]["address_verification_tab_2_visible"] ?? true) ? "t" : "f";

    response(200, $response);

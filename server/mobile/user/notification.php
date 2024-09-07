<?php

    /**
     * @api {post} /mobile/user/notification управление уведомлениями
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String="t","f"} [money] присылать сообщения "необходимо пополнить баланс (31<sup>*</sup>,1,3,10)"
     * @apiBody {String="t","f"} [enable] разрешить входящие текстовые сообщения
     *
     * @apiSuccess {String="t","f"} money присылать сообщения "необходимо пополнить баланс (31<sup>*</sup>,1,3,10)"
     * @apiSuccess {String="t","f"} enable разрешить входящие текстовые сообщения
     *
     * @apiErrorExample Ошибки
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     */

    auth();

    $money = !(int)$device["moneyDisable"];
    $enable = !(int)$device["pushDisable"];

    $households = loadBackend("households");

    // TODO: сделать управление уведомлениями
    if (@$postdata['money'] == 't' && !$money) {
        $households->modifyDevice($device["deviceId"], [ "moneyDisable" => 0 ]);
        $money = 1;
    }

    if (@$postdata['money'] == 'f' && $money) {
        $households->modifyDevice($device["deviceId"], [ "moneyDisable" => 1 ]);
        $money = 0;
    }

    if (@$postdata['enable'] == 't' && !$enable) {
        $households->modifyDevice($device["deviceId"], [ "pushDisable" => 0 ]);
        $enable = 1;
    }

    if (@$postdata['enable'] == 'f' && $enable) {
        $households->modifyDevice($device["deviceId"], [ "pushDisable" => 1 ]);
        $enable = 0;
    }

    response(200, [ "money" => $money ? "t" : "f", "enable" => $enable ? "t" : "f" ]);

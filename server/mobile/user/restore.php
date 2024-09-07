<?php

    /**
     * @api {post} /mobile/user/restore восстановить доступ в ЛК
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup User
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} contract номер договора
     * @apiBody {String} [contactId] контакт на который выслать код подтверждения
     * @apiBody {String{4}} [code] код подтверждения
     * @apiBody {String} [comment] комментарий
     * @apiBody {String="t","f"} [notification="t"] использовать для уведомлений (главный номер, владелец договора)
     *
     * @apiSuccess {Object[]} [-] список возможных контактов
     * @apiSuccess {String} [-.id] идентификтор контакта
     * @apiSuccess {String} [-.contact] контакт (со звездами)
     * @apiSuccess {String="email","phone"} [-.type] тип контакта
     *
     * @apiErrorExample Ошибки
     * 403 требуется авторизация
     * 422 неверный формат данных
     * 404 пользователь не найден
     * 410 авторизация отозвана
     * 424 неверный токен
     */

    auth();

    $custom = @loadBackend('custom');

    if ($custom && method_exists($custom, "userRestorePassword")) {
        $custom->userRestorePassword($postdata);
    } else {
        response();
    }

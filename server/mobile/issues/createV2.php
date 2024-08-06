<?php

    /**
     * @api {post} /issues/createV2 создать заявку
     * @apiDescription **[метод готов]**
     *
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiParam {String="requestCallback","requestFragment","removeAddress","connectServicesNoCommon","connectServicesHasCommon","connectServicesNoNetwork","requestQRCodeOffice","requestQRCodeCourier","requestCredentials"} type тип заявки
     * @apiParam {String} [userName] ФИО
     * @apiParam {String} [inputAddress] адрес, введённый пользователем
     * @apiParam {String} [services] список услуг
     * @apiParam {String} [comments] комментарий к заявке
     * @apiParam {String} [cameraId] идентификатор камеры
     * @apiParam {String} [cameraName] название камеры
     * @apiParam {String} [fragmentDate] дата
     * @apiParam {String} [fragmentTime] время
     * @apiParam {String} [fragmentDuration] длительность фрагмента в минутах
     *
     * @apiSuccess {String} - созданная заявка
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issue_adapter');
    if (!$adapter)
        response(417, false, false, "Не удалось создать заявку.");

    $result = $adapter->createIssue($subscriber['mobile'], $postdata);
    if ($result === false)
        response(417, false, false, "Не удалось создать заявку.");

    if (empty($result['issueId'])) {
        response(417, false, false, "Не удалось создать заявку.");
    } else {
        $issueId = $result['issueId'];
        $support_phone = @$config['mobile']['support_phone'];
        $suffix = "";
        if (isset($support_phone)) {
            $suffix = " По всем вопросам обращайтесь $support_phone";
        }
        if ($result['isNew'] === true) {
            $inbox = loadBackend("inbox");
            $inbox->sendMessage($subscriber['subscriberId'], "Заявка создана", "Создана заявка $issueId." . $suffix);
            response(200, $issueId);
        } else {
            response(417, false, false, "В работе уже есть ранее созданная заявка $issueId." . $suffix);
        }
    }

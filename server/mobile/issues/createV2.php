<?php

    /**
     * @api {post} /mobile/issues/createV2 создать заявку
     * @apiDescription **метод готов**
     *
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String="requestCallback","requestFragment","removeAddress","connectServicesNoCommon","connectServicesHasCommon","connectServicesNoNetwork","requestQRCodeOffice","requestQRCodeCourier","requestCredentials"} type тип заявки
     * @apiBody {String} [userName] ФИО
     * @apiBody {String} [inputAddress] адрес, введённый пользователем
     * @apiBody {String} [services] список услуг
     * @apiBody {String} [comments] комментарий к заявке
     * @apiBody {String} [cameraId] идентификатор камеры
     * @apiBody {String} [cameraName] название камеры
     * @apiBody {String} [fragmentDate] дата
     * @apiBody {String} [fragmentTime] время
     * @apiBody {String} [fragmentDuration] длительность фрагмента в минутах
     *
     * @apiSuccess {String} - созданная заявка
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issue_adapter');
    if (!$adapter) {
        response(417, false, false, i18n("mobile.cantCreateIssue"));
    }

    $result = $adapter->createIssue($subscriber['mobile'], $postdata);
    if ($result === false) {
        response(417, false, false, i18n("mobile.cantCreateIssue"));
    }

    if (empty($result['issueId'])) {
        response(417, false, false, i18n("mobile.cantCreateIssue"));
    } else {
        $issueId = $result['issueId'];
        $support_phone = @$config['mobile']['support_phone'];
        $suffix = "";
        if (isset($support_phone)) {
            $suffix = i18n("mobile.askSupport", $support_phone);
        }
        if ($result['isNew'] === true) {
            $inbox = loadBackend("inbox");
            $inbox->sendMessage($subscriber['subscriberId'], i18n("mobile.issueCreatedTitle"), i18n("mobile.issueCreatedBody", $issueId) . $suffix);
            response(200, $issueId);
        } else {
            response(417, false, false, i18n("mobile.issueAlreadyExists", $issueId) . $suffix);
        }
    }

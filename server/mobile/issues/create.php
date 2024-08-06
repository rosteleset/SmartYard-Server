<?php

    /**
     * @api {post} /issues/create создать заявку
     * @apiDescription **[метод готов]**
     *
     * в cf[11841] всегда (принудительно) прописывается "$userPhone"
     *
     * в cf[11947] всегда (принудительно) прописывается "11208"
     *
     * в cf[11840] всегда (принудительно) прописывается date('d.m.y H:i')
     *
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiParam {Object} issue заявка
     * @apiParam {Object} [customFields] дополнительные поля
     * @apiParam {String[]} [actions] действия после создания
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

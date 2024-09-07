<?php

    /**
     * @api {post} /mobile/issues/create создать заявку
     * @apiDescription **метод готов**
     *
     * в cf11841 всегда (принудительно) прописывается "$userPhone"
     *
     * в cf11947 всегда (принудительно) прописывается "11208"
     *
     * в cf11840 всегда (принудительно) прописывается date('d.m.y H:i')
     *
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {Object} issue заявка
     * @apiBody {Object} [customFields] дополнительные поля
     * @apiBody {String[]} [actions] действия после создания
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

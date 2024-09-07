<?php

    /**
     * @api {post} /mobile/inbox/alert отправить сообщение самому себе
     * @apiVersion 1.0.0
     * @apiDescription **метод готов**
     *
     * @apiGroup Inbox
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} msg сообщение
     * @apiBody {String} action действие
     * @apiBody {String="t","f"} [pushOnly="t"] недублировать отправку через SMS
     */

    auth();

    $msg = @$postdata['msg'];
    if (!$msg) {
        response(406, false, i18n("mobile.error"), i18n("mobile.msgEmpty"));
    }

    $action = @$postdata['action'];
    if (!$action) {
        response(406, false, i18n("mobile.error"), i18n("mobile.actionEmpty"));
    }

    //TODO: сделать обработку pushOnly

    $inbox = loadBackend("inbox");
    $subscriber_id = (int)$subscriber['subscriberId'];
    $inbox->sendMessage($subscriber_id, '', $msg, $action);

    response();

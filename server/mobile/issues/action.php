<?php

    /**
     * @api {post} /mobile/issues/action выполнить переход
     * @apiDescription ***нет проверки на принадлежность заявки именно этому абоненту***
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} key номер заявки
     * @apiBody {String} action действие
     * @apiBody {Object[]} [customFields] дополнительные поля
     * @apiBody {Number} customFields.number номер поля
     * @apiBody {String} customFields.value значение поля
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issue_adapter');
    if (!$adapter)
        response(417, false, false, i18n("mobile.cantChangeIssue"));

    $result = $adapter->actionIssue($postdata);
    if ($result === false)
        response(417, false, false, i18n("mobile.cantChangeIssue"));

    response();

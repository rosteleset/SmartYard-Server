<?php

    /**
     * @api {post} /mobile/issues/listConnectV2 получить список заявок на подключение
     * @apiDescription **метод готов**
     *
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Object[]} - массив заявок
     * @apiSuccess {String} -.key номер заявки
     * @apiSuccess {String} [-.houseId] идентификатор дома
     * @apiSuccess {String} [-.address] адрес
     * @apiSuccess {String="t","f"} -.courier доставка курьером
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 403 запрещено
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issueAdapter');

    $r = [];
    if ($adapter) {
        $issues = $adapter->listConnectIssues($subscriber['mobile']);
        if ($issues !== false)
            $r = $issues;
    }

    response($r ? 200 : 204, $r);

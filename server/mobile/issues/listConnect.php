<?php

    /**
     * @api {post} /mobile/issues/listConnect получить список заявок на подключение
     * @apiDescription **метод готов**
     *
     * cf11841="$userPhone" and cf10011=-1 and Status!="Выполнено" and Status!="Закрыто"
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
     * @apiSuccess {String} [-.services] список подключаемых услуг
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 403 запрещено
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issue_adapter');

    $r = [];
    if ($adapter) {
        $issues = $adapter->listConnectIssues($subscriber['mobile']);
        if ($issues !== false)
            $r = $issues;
    }

    response($r ? 200 : 204, $r);

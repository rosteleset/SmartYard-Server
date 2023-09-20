<?php

/**
 * @api {post} /issues/listConnect получить список заявок на подключение
 * @apiDescription **[метод готов]**
 *
 * cf[11841]="$userPhone" and cf[10011]=-1 and Status!="Выполнено" and Status!="Закрыто"
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

    /*
    $user_phone = substr($bearer['id'], 1);

    jira_require();

    try {
        $issues = $jiraSoap->getIssuesFromJqlSearch($jiraAuth, "cf[11841]=\"$user_phone\" and cf[10011]=-1 and Status!=\"Выполнено\" and Status!=\"Закрыто\"", 32);
    } catch (Exception $ex) {
        $issues = [];
    }

    $r = [];

    $already = [];

    $qr = pg_query("select house_id from address.flats where flat_id in (select flat_id from domophones.z_all_flats where id='{$bearer['id']}' and type in ('inner', 'owner'))");
    while ($row = pg_fetch_assoc($qr)) {
        $already[$row['house_id']] = true;
    }

    if ($issues) {
        foreach ($issues as $indx => $issue) {
            $i = [];
            $i['key'] = $issue->key;
            $cf_11140 = false;
            $cf_10941 = false;
            foreach ($issue->customFieldValues as $cf_indx => $cf) {
                if ($cf->customfieldId == 'customfield_11140') {
                    $cf_11140 = $cf->values[0];
                }
                if ($cf->customfieldId == 'customfield_10941') {
                    $cf_10941 = $cf->values[0];
                }
            }
            if ($cf_11140) {
                if (@$already[$cf_11140]) continue;
                $i['houseId'] = $cf_11140;
                $i['address'] = pg_fetch_result(pg_query("select address.house_address($cf_11140, 3)"), 0);
            } else {
                $a = @trim(explode("\n", trim(explode("Адрес, введённый пользователем:", $issue->description)[1]))[0]);
                if ($a) {
                    $i['address'] = $a;
                }
            }
            $s = @trim(explode("\n", trim(explode("Список подключаемых услуг:", $issue->description)[1]))[0]);
            if ($s) {
                $i['services'] = $s;
            }
            $i['courier'] = ($cf_10941 == 'Курьер')?'t':'f';
            $r[] = $i;
        }
    }

    response($r?200:204, $r);
*/
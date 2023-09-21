<?php

/**
 * @api {post} /issues/action выполнить переход
 * @apiDescription ***нет проверки на пренадлежность заявки именно этому абоненту***
 * @apiVersion 1.0.0
 * @apiGroup Issues
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} key номер заявки
 * @apiParam {String} action действие
 * @apiParam {Object[]} [customFields] дополнительные поля
 * @apiParam {Number} customFields.number номер поля
 * @apiParam {String} customFields.value значение поля
 *
 * @apiErrorExample Ошибки
 * 422 неверный формат данных
 * 417 ожидание не удалось
 */

function actionIssue($adapter, $issueId, $action, $data)
{
    if ($action === "Jelly.Закрыть авто")
        return $adapter->closeIssue($issueId)[0] ?? false;

    if ($action === "Jelly.Способ доставки") {
        $is_courier = true;
        foreach ($data as $cf) {
            if ($cf['number'] === '10941' && $cf['value'] !== 'Курьер')
                $is_courier = false;
        }

        if (!$is_courier)
            return $adapter->closeIssue($issueId)[0] ?? false;

        return true;
    }

    return false;
}

auth();

$adapter = loadBackend('issue_adapter');
actionIssue($adapter, @$postdata['key'], @$postdata['action'], @$postdata['customFields']);
response();

/*
jira_require();

jira_action(@$postdata['key'], @$postdata['action'], @$postdata['customFields']);

response();
*/

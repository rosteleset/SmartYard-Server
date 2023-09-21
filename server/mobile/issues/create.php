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

function createIssue($adapter, $phone, $data)
{
    $description = $data['issue']['description'];
    $summary = $data['issue']['summary'];

    if (strpos($description, 'Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры') !== false) {
        return $adapter->createIssueForDVRFragment($phone, $description, null, null, null, null);
    } elseif (strpos($summary, 'Авто: Звонок с приложения') !== false) {
        if (strpos($description, 'Выполнить звонок клиенту по запросу с приложения') !== false
            || strpos($description, 'Выполнить звонок клиенту по запросу из приложения') !== false)
            return $adapter->createIssueCallback($phone);
        elseif (strpos($description, 'Выполнить звонок клиенту для напоминания номера договора') !== false)
            return $adapter->createIssueForgotEverything($phone);
    } elseif (strpos($description, 'Подготовить конверт') !== false) {
        $lat = $data['customFields']['10743'];
        $lon = $data['customFields']['10744'];
        return $adapter->createIssueConfirmAddress($phone, $description, null, null, $lat, $lon);
    } elseif (strpos($description, 'Удаление адреса') !== false) {
        $lat = $data['customFields']['10743'];
        $lon = $data['customFields']['10744'];
        return $adapter->createIssueDeleteAddress($phone, $description, null, null, $lat, $lon, null);
    } elseif (strpos($description, 'Список подключаемых услуг') !== false
        && strpos($description, 'Требуется подтверждение адреса') === false) {
        $lat = $data['customFields']['10743'];
        $lon = $data['customFields']['10744'];
        return $adapter->createIssueUnavailableServices($phone, $description, null, null, $lat, $lon, null);
    } elseif (strpos($description, 'Выполнить звонок клиенту') !== false) {
        $lat = $data['customFields']['10743'];
        $lon = $data['customFields']['10744'];
        return $adapter->createIssueAvailableWithoutSharedServices($phone, $description, null, null, $lat, $lon, null);
    }

    return false;
}

auth();

// error_log("\n\n" . print_r($postdata, true) . "\n\n");

$adapter = loadBackend('issue_adapter');
if (!$adapter)
    response(417, false, false, "Не удалось создать заявку.");

$result = createIssue($adapter, $subscriber['mobile'], $postdata);
if ($result === false)
    response(200, "_");

if (empty($result['issueId'])) {
    response(417, false, false, "Не удалось создать заявку.");
} else {
    $issueId = $result['issueId'];
    if ($result['isNew'] === true) {
        $inbox = loadBackend("inbox");
        $inbox->sendMessage($subscriber['subscriberId'], "Заявка создана", "Создана заявка $issueId. По всем вопросам обращайтесь 84752429999");
        response(200, $issueId);
    } else {
        response(417, false, false, "В работе уже есть ранее созданная заявка $issueId. По всем вопросам обращайтесь 84752429999");
    }
}

/*
    jira_require();

    $issue = @$postdata['issue'];

    $user_phone = substr($bearer['id'], 1);

    $custom_fields = [];
    $postdata['customFields'][11841] = $user_phone;
    $postdata['customFields'][11947] = 11208;
    $postdata['customFields'][11840] = date('d.m.y H:i');

    $client_id = @$postdata['customFields'][10011];
    foreach (@$postdata['customFields'] as $cf => $v) {
        $custom_fields[] = [ "customfieldId" => "customfield_{$cf}", "key" => null, "values" => [ $v ]];
    }

    try {
        $doubles = $jiraSoap->getIssuesFromJqlSearch($jiraAuth, "\"Идентификатор клиента\" in (-1, -3, -5) AND status not in (Выполнено, Закрыто) AND \"+7\"=$user_phone", 256);
    } catch (Exception $ex) {
        response(417, false, $ex->getCode(), $ex->getMessage());
    }

    if (count($doubles)) {
        $t = [];
        for ($i = 0; $i < count($doubles); $i++) {
            $t[] = $doubles[$i]->key;
        }
        $custom_fields[] = [ "customfieldId" => "customfield_12541", "key" => null, "values" => [ implode(',', $t) ]];
    }

    $issue['customFieldValues'] = $custom_fields;
    try {
        $issue = $jiraSoap->createIssue($jiraAuth, $issue);
    } catch (Exception $ex) {
        response(417, false, $ex->getCode(), $ex->getMessage());
    }

    foreach (@$postdata['actions'] as $action) {
        jira_action($issue->key, $action);
    }

    $msg = trim(mysqli_escape_string($mysql, "Создана заявка {$issue->key} по всем вопросам обращайтесь 84752429999"));
    $id = $bearer['id'];
    $id[0] = '7';

    mysql("insert into dm.inbox (date, id, msg, ext_id, push_only, action) values (now(), '$id', '$msg', md5(now() + rand()), true, '')");

    response(200, $issue->key);
*/

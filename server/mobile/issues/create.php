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

function createIssue($adapter, $phone, $data) {
    $description = $data['issue']['description'];
    if (strpos($description, 'Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры') !== false) {
        return $adapter->createIssueForDVRFragment($phone, $description, null, null, null, null);
    }
}

    auth();
    $adapter = loadBackend('issue_adapter');
    $result = createIssue($adapter, $subscriber['mobile'], $postdata);
    if ($result !== false)
        response(200, $result);
    else
        response(417, false, false, "Не удалось создать заявку.");

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

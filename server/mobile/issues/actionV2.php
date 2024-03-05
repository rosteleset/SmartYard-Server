<?php

/**
 * @api {post} /issues/actionV2 выполнить переход
 * @apiDescription ***нет проверки на принадлежность заявки именно этому абоненту***
 * @apiVersion 1.0.0
 * @apiGroup Issues
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} key номер заявки
 * @apiParam {String="close","changeQRDeliveryType"} action действие
 * @apiParam {String="office","courier"} [deliveryType] способ доставки
 *
 * @apiErrorExample Ошибки
 * 422 неверный формат данных
 * 417 ожидание не удалось
 */

auth();

$adapter = loadBackend('issue_adapter');
if (!$adapter)
    response(417, false, false, "Не удалось изменить заявку.");

$result = $adapter->actionIssue($postdata);
if ($result === false)
    response(417, false, false, "Не удалось изменить заявку.");

response();

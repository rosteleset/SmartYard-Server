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

auth();
response();

/*
jira_require();

jira_action(@$postdata['key'], @$postdata['action'], @$postdata['customFields']);

response();
*/

<?php

/**
 * @api {post} /issues/comment оставить комментарий в заявке
 * @apiDescription ***нет проверки на пренадлежность заявки именно этому абоненту***
 * @apiVersion 1.0.0
 * @apiGroup Issues
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} key номер заявки
 * @apiParam {String} comment комментарий
 *
 * @apiErrorExample Ошибки
 * 422 неверный формат данных
 * 417 ожидание не удалось
 */

auth();

jira_require();

$jiraSoap->addComment($jiraAuth, @$postdata['key'], [ 'body' => @$postdata['comment'] ]);

response();

<?php
/**
 * @api {post} /cctv/recPrepare запросить фрагмент архива
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {Number} id идентификатор камеры
 * @apiParam {String="Y-m-d H:i:s"} from начало фрагмента
 * @apiParam {String="Y-m-d H:i:s"} to конец фрагмента
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Number} - идентификатор фрагмента
 */

use Selpol\Task\Tasks\RecordTask;

$user = auth();

$cameraId = (int)@$postdata['id'];

// приложение везде при работе с архивом передаёт время по часовому поясу Москвы.
date_default_timezone_set('Europe/Moscow');
$from = strtotime(@$postdata['from']);
$to = strtotime(@$postdata['to']);

if (!$cameraId || !$from || !$to)
    response(404);

$dvr_exports = backend("dvr_exports");

// проверяем, не был ли уже запрошен данный кусок из архива.
$check = $dvr_exports->checkDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

if (@$check['id'])
    response(200, $check['id']);

// если такой кусок ещё не запрашивали, то добавляем запрос на скачивание.
$result = (int)$dvr_exports->addDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

task(new RecordTask($user["subscriberId"], $result))->low()->dispatch();

response(200, $result);
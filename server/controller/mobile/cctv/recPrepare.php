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

$user = auth();

$cameraId = (int)@$postdata['id'];

// приложение везде при работе с архивом передаёт время по часовому поясу Москвы.
date_default_timezone_set('Europe/Moscow');
$from = strtotime(@$postdata['from']);
$to = strtotime(@$postdata['to']);

if (!$cameraId || !$from || !$to)
    response(404);

$dvr_exports = loadBackend("dvr_exports");

// проверяем, не был ли уже запрошен данный кусок из архива.
$check = $dvr_exports->checkDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

if (@$check['id'])
    response(200, $check['id']);

// если такой кусок ещё не запрашивали, то добавляем запрос на скачивание.
$res = (int)$dvr_exports->addDownloadRecord($cameraId, $user["subscriberId"], $from, $to);

session_write_close();
exec("php " . __DIR__ . "/../../cli.php --run-record-download=$res >/dev/null 2>/dev/null &");

response(200, $res);
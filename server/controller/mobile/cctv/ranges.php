<?php
/**
 * @api {post} /cctv/ranges получить список доступных периодов в архиве
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {Number} [cameraId] идентификатор камеры
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String} stream название потока
 * @apiSuccess {Object[]} ranges массив интервалов
 * @apiSuccess {Number} ranges.from метка начала
 * @apiSuccess {Number} ranges.duration продолжительность периода
 */

auth();

$camera_id = (int)@$postdata['cameraId'];

$cameras = loadBackend("cameras");

$cam = $cameras->getCamera($camera_id);
if (!$cam) {
    response(404);
}

$ranges = loadBackend("dvr")->getRanges($cam, $subscriber['subscriberId']);

response(200, $ranges);
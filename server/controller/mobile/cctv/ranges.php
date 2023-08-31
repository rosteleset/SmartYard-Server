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

$user = auth();

$camera_id = (int)@$postdata['cameraId'];

$cameras = backend("cameras");

$cam = $cameras->getCamera($camera_id);
if (!$cam) {
    response(404);
}

$ranges = backend("dvr")->getRanges($cam, $user['subscriberId']);

response(200, $ranges);
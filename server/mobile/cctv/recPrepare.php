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

auth();
response();

/*
$cam = (int)@$postdata['id'];
$from = strtotime(@$postdata['from']);
$to = strtotime(@$postdata['to']);

$id = (int)demo('download', [ 'phone' => $bearer['id'], 'cameraId' => $cam, 'timeStart' => $from, 'timeEnd' => $to ], true);

if ($id) {
    response(200, $id);
} else {
    response();
}
*/

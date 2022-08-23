<?php

/**
 * @api {post} /cctv/recDownload запросить url фрагмента архива
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {Number} id идентификатор фрагмента
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {String} - url
 */

auth();
response();

/*
$id = (int)@$postdata['id'];

$url = demo('downloadUrl', [ 'phone' => $bearer['id'], 'downloadId' => $id ], true);

if ($url) {
    response(200, $url);
} else {
    response();
}
*/

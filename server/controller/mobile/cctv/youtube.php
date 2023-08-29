<?php

/**
 * @api {post} /cctv/youtube получить список роликов на YouTube
 * @apiVersion 1.0.0
 * @apiDescription ***почти готов***
 *
 * @apiGroup CCTV
 *
 * @apiParam {Number} [id] id камеры
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiSuccess {Object[]} - массив c роликами
 * @apiSuccess {Number} -.id id камеры
 * @apiSuccess {String="Y-m-d H:i:s"} -.eventTime время события
 * @apiSuccess {integer} [-.timezone] часовой пояс (default - Moscow Time)
 * @apiSuccess {String} -.title заголовок
 * @apiSuccess {String} -.description описание
 * @apiSuccess {String} -.thumbnailsDefault превью
 * @apiSuccess {String} -.thumbnailsMedium превью
 * @apiSuccess {String} -.thumbnailsHigh превью
 * @apiSuccess {String} -.url ссылка на ролик
 * @apiSuccess {String} -.addressLine адрес (?)
 */

auth();
response();

/*
$id = (int)@$postdata['id'];

if ($id) {
    $vidos = demo('youtubeVideos', [ 'cameraId' => $id ]);
} else {
    $vidos = demo('youtubeVideos');
}

usort($vidos, function ($a, $b) {
    if ($a['eventTime'] > $b['eventTime']) {
        return -1;
    } else
        if ($a['eventTime'] < $b['eventTime']) {
            return 1;
        } else {
            return 0;
        }
});

foreach ($vidos as &$v) {
    $v['id'] = $v['cameraId'];
    $v['eventTime'] = date('Y-m-d H:i:s', $v['eventTime']);
    unset($v['cameraId']);
}

if (count($vidos)) {
    response(200, $vidos);
} else {
    response();
}
*/

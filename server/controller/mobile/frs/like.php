<?php

/**
 * @api {post} /frs/like "лайкнуть" (свой)
 * @apiVersion 1.0.0
 * @apiDescription **[в работе]**
 *
 * @apiGroup FRS
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} event идентификатор события (uuid)
 * @apiParam {String} comment комментарий
 *
 * @apiSuccess {Object} - объект
 * @apiSuccess {Number} -.faceId FaceId
 */

use backends\plog\plog;
use backends\frs\frs;

$user = auth(5);

$plog = loadBackend("plog");
if (!$plog) response(422);

$frs = loadBackend("frs");
if (!$frs) response(422);

$event_uuid = $postdata['event'];

if (!$event_uuid)
    response(405, false, 'Событие не указано');

$event_data = $plog->getEventDetails($event_uuid);

if (!$event_data)
    response(404, false, 'Событие не найдено');

if ($event_data[plog::COLUMN_PREVIEW] == plog::PREVIEW_NONE)
    response(403, false, 'Нет кадра события');

$flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);

$flat_id = (int)$event_data[plog::COLUMN_FLAT_ID];
$f = in_array($flat_id, $flat_ids);

if (!$f)
    response(403, false, 'Квартира не найдена');

// TODO: check if FRS is allowed for flat_id

$households = loadBackend('households');
$domophone = json_decode($event_data[plog::COLUMN_DOMOPHONE], false);
$entrances = $households->getEntrances('domophoneId', ['domophoneId' => $domophone->domophone_id, 'output' => $domophone->domophone_output]);

if ($entrances && $entrances[0]) {
    $cameras = $households->getCameras('id', $entrances[0]['cameraId']);

    if ($cameras && $cameras[0]) {
        $img_uuid = $event_data[plog::COLUMN_IMAGE_UUID];
        $url = @$config['api']['mobile'] . '/address/plogCamshot/$img_uuid';
        $face = json_decode($event_data[plog::COLUMN_FACE], true);
        $result = $frs->registerFace($cameras[0], $event_uuid, $face['left'] ?? 0, $face['top'] ?? 0, $face['width'] ?? 0, $face['height'] ?? 0);

        if (!isset($result[frs::P_FACE_ID]))
            response(406, $result[frs::P_MESSAGE]);

        $face_id = (int)$result[frs::P_FACE_ID];
        $subscriber_id = (int)$user['subscriberId'];

        $frs->attachFaceId($face_id, $flat_id, $subscriber_id);

        response(200);
    }
}

response();
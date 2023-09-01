<?php

/**
 * @api {post} /frs/disLike "дизлайкнуть" (чужой, ложное срабатывание, разонравился)
 * @apiVersion 1.0.0
 * @apiDescription **[в работе]**
 *
 * для ленты событий указывать event (flat и face будут проигнорированы), для списка лиц указывать flat или flat и face
 *
 * @apiGroup FRS
 *
 * @apiHeader {String} authorization токен авторизации
 *
 * @apiParam {String} [event] идентификатор события (для ленты событий)
 * @apiParam {Number} [flatId] идентификатор квартиры (адрес) (для списка лиц)
 * @apiParam {Number} [faceId] идентификатор "лица" (для списка лиц)
 */

use backends\plog\plog;

$user = auth(5);

$plog = backend("plog");
if (!$plog) response(422);

$frs = backend("frs");
if (!$frs) response(422);

$event_uuid = @$postdata['event'];

$face_id = null;
$face_id2 = null;

if ($event_uuid) {
    $event_data = $plog->getEventDetails($event_uuid);
    if (!$event_data)
        response(404, false, 'Событие не найдено');

    $flat_id = (int)$event_data[plog::COLUMN_FLAT_ID];

    $face = json_decode($event_data[plog::COLUMN_FACE]);
    if (isset($face->faceId) && $face->faceId > 0)
        $face_id = (int)$face->faceId;

    $face_id2 = $frs->getRegisteredFaceId($event_uuid);

    if ($face_id2 === false)
        $face_id2 = null;
} else {
    $flat_id = @(int)$postdata['flatId'];
    $face_id = @(int)$postdata['faceId'];
}

if (($face_id === null || $face_id <= 0) && ($face_id2 === null || $face_id2 <= 0))
    response(403, false, 'face_id не найден');

$flat_ids = array_map(static fn(array $item) => $item['flatId'], $user['flats']);
$f = in_array($flat_id, $flat_ids);

if (!$f)
    response(403, false, 'Квартира не найдена');

// TODO: check if FRS is allowed for flat_id

$flat_owner = false;

foreach ($user['flats'] as $flat)
    if ($flat['flatId'] == $flat_id) {
        $flat_owner = ($flat['role'] == 0);

        break;
    }

if ($flat_owner) {
    if ($face_id > 0) $frs->detachFaceIdFromFlat($face_id, $flat_id);
    if ($face_id2 > 0) $frs->detachFaceIdFromFlat($face_id2, $flat_id);
} else {
    $subscriber_id = (int)$user['subscriberId'];

    if ($face_id > 0) $frs->detachFaceId($face_id, $subscriber_id);
    if ($face_id2 > 0) $frs->detachFaceId($face_id2, $subscriber_id);
}

response();
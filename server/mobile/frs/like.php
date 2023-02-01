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

auth(5);

$plog = loadBackend("plog");
if (!$plog) {
    response(422);
}

$frs = loadBackend("frs");
if (!$frs) {
    response(422);
}

$event_uuid = $postdata['event'];
if (!$event_uuid) {
    response(405, false, 'Событие не указано');
}

$event_data = $plog->getEventDetails($event_uuid);
if (!$event_data) {
    response(404, false, 'Событие не найдено');
}

if ($event_data[plog::COLUMN_PREVIEW] == plog::PREVIEW_NONE) {
    response(403, false, 'Нет кадра события');
}

$flat_ids = array_map(function($item) { return $item['flatId']; }, $subscriber['flats']);
$flat_id = (int)$event_data[plog::COLUMN_FLAT_ID];
$f = in_array($flat_id, $flat_ids);
if (!$f) {
    response(403, false, 'Квартира не найдена');
}

// TODO: check if FRS is allowed for flat_id

$households = loadBackend("households");
$domophone = json_decode($event_data[plog::COLUMN_DOMOPHONE], false);
$entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $domophone->domophone_id, "output" => $domophone->domophone_output ]);
if ($entrances && $entrances[0]) {
    $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
    if ($cameras && $cameras[0]) {
        $img_uuid = $event_data[plog::COLUMN_IMAGE_UUID];
        $url = @$config["api"]["mobile"] . "/address/plogCamshot/$img_uuid";
        $face = json_decode($event_data[plog::COLUMN_FACE], false);
        $result = $frs->registerFace($cameras[0], $event_uuid, $face->left, $face->top, $face->width, $face->height);
        if (!isset($result[frs::P_FACE_ID])) {
            response(406, $result[frs::P_MESSAGE]);
        }

        $face_id = (int)$result[frs::P_FACE_ID];
        $subscriber_id = (int)$subscriber['subscriberId'];
        $frs->attachFaceId($face_id, $flat_id, $subscriber_id);
        response(200);
    }
}

response();

/*
    $uuid = mysqli_escape_string($mysql, @$postdata['event']);

    if (!$uuid) {
        response(405, false, 'Событие не указано');
    }

    $event = mysqli_fetch_assoc(clickhouse("select * from plog where uuid = '$uuid'"));

    if (!$event) {
        response(404, false, 'Событие не найдено');
    }

    if ((int)$event['object_type'] !== 0) {
        response(404, false, 'Неизвестный тип объекта');
    }

    $flat_id = (int)$event['flat_id'];

    $f = in_array($flat_id, all_flats());

    if (!$flat_id || !$f) {
        response(403, false, 'Квартира не найдена');
    }

    $already = (int)mysqli_fetch_assoc(mysql("select count(*) as already from dm.faceflats where flat_id=$flat_id"))['already'];

    if ($already > 100) {
        response(429, false, 'Слишком много объектов уже добавлено');
    }

    if ($event['preview'] != 2) {
        response(403, false, 'Нет "картинки"');
    }

    $detail = explode(':', $event['detail']);
    if (!(int)@$detail[1]) {
        response(403, false, 'Ошибка обработки события');
    }

    $face_id = (int)$detail[1];

    $url = explode('-', explode(' ', $event['date'])[0]);
    $url = "https://static.dm.lanta.me/{$url[0]}-{$url[1]}-{$url[2]}/{$event['image'][0]}/{$event['image'][1]}/{$event['image'][2]}/{$event['image'][3]}/{$event['image']}.jpg";

    $domophone_id = $event['object_id'];
    $cam = mysqli_fetch_assoc(mysql("select * from dm.cams where domophone_id = $domophone_id"));

    if ((int)$event['event'] == 5) {
        $req = [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json",
            'content' => json_encode([
                'streamId' => $cam['stream_id'],
                'url' => $url,
                'left' => (int)$detail[3],
                'top' => (int)$detail[4],
                'width' => (int)$detail[5],
                'height' => (int)$detail[6],
            ]),
        ];
    } else {
        $req = [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json",
            'content' => json_encode([
                'streamId' => $cam['stream_id'],
                'url' => $url,
                'left' => (int)$detail[2],
                'top' => (int)$detail[3],
                'width' => (int)$detail[4],
                'height' => (int)$detail[5],
            ]),
        ];
    }

//    response(200, $req);

    $face = @json_decode(file_get_contents("{$cam['frs_server']}/registerFace", false, stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
        'http' => $req,
    ])), true);

    if (!$face || !@$face['data'] || !@$face['data']['faceId'] || !@$face['data']['faceImage']) {
        response(502, false, 'Ошибка регистрации');
    }

    $faceImage = file_get_contents($face['data']['faceImage']);
    $faceId = (int)$face['data']['faceId'];
    $faceUUID = md5($faceImage);
    $faceUUID = substr($faceUUID, 0, 8) . '-' . substr($faceUUID, 8, 4) . '-' . substr($faceUUID, 12, 4) . '-' . substr($faceUUID, 16, 4) . '-' . substr($faceUUID, 20);

    $path = "{$faceUUID[0]}/{$faceUUID[1]}/{$faceUUID[2]}/{$faceUUID[3]}/{$faceUUID[4]}";
    if (!is_dir("/storage/faces/$path")) {
        mkdir_r("/storage/faces/$path");
    }
    file_put_contents("/storage/faces/$path/$faceUUID.jpg", $faceImage);

    /*
     * dm.faces - линки между faceId (external) и фактической картинкой РАСПОЗНАННОГО лица
     * dm.face2face - линки между faceId (external) и событиями
     * dm.likes - кто лайкнул событие (тут начинается уебище)
     * dm.faceflats - списки поквартирных лайков в разрезе жильцов, специально для посетителей публичных домов (судя по тех. заданию [обосраться со смеху] у нас таких больше 99% от жилого фонда)
     */

    /*mysql("update dm.face2face set external_face_id = $faceId where face_id = $face_id");
    mysql("insert ignore into dm.faces (external_face_id) values ($faceId)");
    mysql("update dm.faces set image = '$faceUUID' where external_face_id = $faceId");*/

    /*
     * Жуткое, нахуй не нужное уебище!
     */

    /*$comment = mysqli_escape_string($mysql, @$postdata['comment']);
    mysql("insert ignore into dm.faceflats (flat_id, external_face_id, owner) values ($flat_id, $faceId, '{$bearer['id']}')");
    mysql("update dm.faceflats set comment = '$comment' where flat_id = $flat_id and external_face_id = $faceId and owner = '{$bearer['id']}'");*/

    /*
     * Еще более жуткое и нахуй не нужное уебище!
     */

    /*mysql("insert ignore into dm.likes (event, owner) values ('$uuid', '{$bearer['id']}')");
    mysql("update dm.likes set flat_id = $flat_id, external_face_id = $faceId where event = '$uuid' and owner = '{$bearer['id']}'");

    response(200, [
        'faceId' => $faceId,
    ]);
*/

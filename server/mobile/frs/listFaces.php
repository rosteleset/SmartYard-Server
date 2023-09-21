<?php

/**
 * @api {post} /frs/listFaces список "лиц"
 * @apiVersion 1.0.0
 * @apiDescription **[в работе]**
 *
 * @apiGroup FRS
 *
 * @apiHeader {string} authorization токен авторизации
 *
 * @apiParam {integer} flatId идентификатор квартиры (адрес)
 * @apiSuccess {object[]} - массив объектов
 * @apiSuccess {string} -.faceId идентификатор "лица"
 * @apiSuccess {string} -.image url картинки
 */

use backends\frs\frs;

auth(3600);

$flat_id = (int)@$postdata['flatId'];
if (!$flat_id) {
    response(422);
}

$frs = loadBackend("frs");
if (!$frs) {
    response(422);
}

$flat_ids = array_map(function($item) { return $item['flatId']; }, $subscriber['flats']);
$f = in_array($flat_id, $flat_ids);
if (!$f) {
    response(403, false, 'Квартира не найдена');
}

// TODO: check if FRS is allowed for flat_id

$flat_owner = false;
foreach ($subscriber['flats'] as $flat) {
    if ($flat['flatId'] == $flat_id) {
        $flat_owner = ($flat['role'] == 0);
        break;
    }
}

$subscriber_id = (int)$subscriber['subscriberId'];
$faces = $frs->listFaces($flat_id, $subscriber_id, $flat_owner);
$result = [];
foreach ($faces as $face) {
    $result[] = ['faceId' => strval($face[frs::P_FACE_ID]), 'image' => @$config["api"]["mobile"] . "/address/plogCamshot/" . $face[frs::P_FACE_IMAGE]];
}

if ($result) {
    response(200, $result);
} else {
    response(204);
}

    /*
     * Disclaimer: использование "иерархии" владелец\не владелец считаю в данном случае избыточным и вредоносным,
     * повлиять на это решение возможности нет, ну и хуй с ним
     * (общий список свой\чужой проще в реализации и не так убивает производительность)
     */

/*
    $flat_id = @(int)$postdata['flatId'];

    if (!in_array($flat_id, all_flats())) {
        response(404);
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);

    $likes_by_external_id = [];
    $qr = mysql("select external_face_id, owner from dm.faceflats where flat_id = $flat_id"); // а это еще больший пиздец
    while ($row = mysqli_fetch_assoc($qr)) {
        $likes_by_external_id[$row['external_face_id']][] = $row['owner'];
    }

    $resp = [];
    $qr = mysql("select external_face_id, image from dm.faceflats left join dm.faces using (external_face_id) where flat_id = $flat_id group by external_face_id");
    while ($row = mysqli_fetch_assoc($qr)) {
        $u = $row['image'];

        //
        // !!!! переделать !!!!
        //

        if ($my_relation_to_this_flat == 'owner' || in_array($bearer['id'], $likes_by_external_id[$row['external_face_id']])) {
            $resp[] = [
                'faceId' => $row['external_face_id'],
                'image' => "https://faces.dm.lanta.me/{$u[0]}/{$u[1]}/{$u[2]}/{$u[3]}/{$u[4]}/{$u}.jpg",
                // потом убрать, т.к. смысла в этом нет
                'canDislike' => 't',
                'canDisLike' => 't',
            ];
        }
    }

    if (count($resp)) {
        response(200, $resp);
    } else {
        response();
    }
*/
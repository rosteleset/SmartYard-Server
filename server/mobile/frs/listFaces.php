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
 * @apiSuccess {integer} -.faceId идентификатор "лица"
 * @apiSuccess {string} -.image url картинки
 */

    auth(3600);

    /*
     * Disclaimer: использование "иерархии" владелец\не владелец считаю в данном случае избыточным и вредоносным,
     * повлиять на это решение возможности нет, ну и хуй с ним
     * (общий список свой\чужой проще в реализации и не так убивает производительность)
     */

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

        /*
         * !!!! переделать !!!!
         */

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

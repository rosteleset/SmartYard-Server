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

    auth(5);

    /*
     * Disclaimer: использование "иерархии" владелец\не владелец считаю в данном случае избыточным и вредоносным,
     * повлиять на это решение возможности нет, ну и хуй с ним
     * (общий список свой\чужой проще в реализации и не так убивает производительность)
     */

    $face_id = false;
    $external_face_id = false;
    $uuid = false;

    if (@$postdata['event']) {
        $uuid = mysqli_escape_string($mysql, @$postdata['event']);

        if (!$uuid) {
            response(405, false, 'Событие не указано');
        }

        $event = mysqli_fetch_assoc(clickhouse("select * from plog where uuid = '$uuid'"));

        $flat_id = $event['flat_id'];
        $face_id = (int)(explode(':', $event['detail'])[1]);
        if ($event['event'] == 5) {
            $external_face_id = $face_id;
        } else {
            if ($event['event'] != 7) {
                $external_face_id = mysqli_fetch_assoc(mysql("select external_face_id from dm.face2face where face_id = $face_id"))['external_face_id'];
            }
        }
    } else {
        $flat_id = @(int)$postdata['flatId'];
        $external_face_id = @(int)$postdata['faceId'];
    }

    if (!in_array($flat_id, all_flats())) {
        response(404, false, 'Квартира не найдена');
    }

    if (!$external_face_id) {
        response(403, false, 'Отпечаток не найден');
    }

    $my_relation_to_this_flat = flat_relation($flat_id, $bearer['id']);

    if ($my_relation_to_this_flat == 'owner') {
        mysql("delete from dm.likes where flat_id = $flat_id and external_face_id = $external_face_id");
        mysql("delete from dm.faceflats where flat_id = $flat_id and external_face_id = $external_face_id");
    } else {
        mysql("delete from dm.likes where flat_id = $flat_id and external_face_id = $external_face_id and owner = '{$bearer['id']}'");
        mysql("delete from dm.faceflats where flat_id = $flat_id and external_face_id = $external_face_id and owner = '{$bearer['id']}'");
    }

    response();
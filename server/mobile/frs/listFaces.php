<?php

    /**
     * @api {post} /mobile/frs/listFaces список "лиц"
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup FRS
     *
     * @apiHeader {string} authorization токен авторизации
     *
     * @apiBody {integer} flatId идентификатор квартиры (адрес)
     * @apiBody {integer} [groupId] идентификатор группы
     *
     * @apiSuccess {object[]} - массив объектов
     * @apiSuccess {string} -.faceId идентификатор "лица"
     * @apiSuccess {string} -.image url картинки
     * @apiSuccess {integer} [-.groupId] идентификатор группы
     */

    use backends\frs\frs;

    auth();

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
        response(403, false, i18n("mobile.404"));
    }

    // TODO: check if FRS is allowed for flat_id

    $flat_owner = false;
    foreach ($subscriber['flats'] as $flat) {
        if ($flat['flatId'] == $flat_id) {
            $flat_owner = ($flat['role'] == 0);
            break;
        }
    }

    $group_id = (int)@$postdata['groupId'];

    $subscriber_id = (int)$subscriber['subscriberId'];
    $faces = $frs->listFacesFrs($flat_id, $subscriber_id, $flat_owner);
    $result = [];
    foreach ($faces as $face) {
        $item = ['faceId' => strval($face[frs::P_FACE_ID]), 'image' => @$config["api"]["mobile"] . "/address/plogCamshot/" . $face[frs::P_FACE_IMAGE]];
        $grp_id = $face[frs::P_GROUP_ID] ?? null;
        if (isset($grp_id)) {
            $item[frs::P_GROUP_ID] = (int)$grp_id;
        }
        if ($group_id > 0 && isset($grp_id) && $group_id == $grp_id || $group_id == 0) {
            $result[] = $item;
        }
    }

    if ($result) {
        response(200, $result);
    } else {
        response(204);
    }

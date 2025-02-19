<?php

    /**
     * @api {post} /mobile/frs/like "лайкнуть" (свой)
     * @apiVersion 1.0.0
     * @apiDescription **в работе**
     *
     * @apiGroup FRS
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} event идентификатор события (uuid)
     * @apiBody {String} comment комментарий
     *
     * @apiSuccess {Object} - объект
     * @apiSuccess {Number} -.faceId FaceId
     */

    use backends\plog\plog;
    use backends\frs\frs;

    auth();

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
        response(405, false, i18n("mobile.404"));
    }

    $event_data = $plog->getEventDetails($event_uuid);
    if (!$event_data) {
        response(404, false, i18n("mobile.404"));
    }

    if ($event_data[plog::COLUMN_PREVIEW] == plog::PREVIEW_NONE) {
        response(403, false, i18n("mobile.404"));
    }

    $flat_ids = array_map(function($item) { return $item['flatId']; }, $subscriber['flats']);
    $flat_id = (int)$event_data[plog::COLUMN_FLAT_ID];
    $f = in_array($flat_id, $flat_ids);
    if (!$f) {
        response(403, false, i18n("mobile.404"));
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
            $result = $frs->registerFaceFrs($cameras[0], $event_uuid, $face->left, $face->top, $face->width, $face->height);
            if (!isset($result[frs::P_FACE_ID])) {
                response(406, $result[frs::P_MESSAGE]);
            }

            $face_id = (int)$result[frs::P_FACE_ID];
            $subscriber_id = (int)$subscriber['subscriberId'];
            $frs->attachFaceIdFrs($face_id, $flat_id, $subscriber_id);
            response(200);
        }
    }

    response();

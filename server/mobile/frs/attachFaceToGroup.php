<?php

/**
 * @api {post} /mobile/frs/attachFaceToGroup attach the face to the subscriber's group
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup FRS
 *
 * @apiHeader {string} authorization authorization token
 *
 * @apiBody {string} faceId face identifier
 * @apiBody {integer} groupId group identifier
 *
 * @apiErrorExample Errors
 * 417 Failed to attach the face to the group
 * 422 Invalid parameter
 * 500 Internal server error
 */

auth();

$subscriber_id = (int)$subscriber['subscriberId'];

$households = loadBackend("households");
if (!$households) {
    response(422);
}

$frs = loadBackend("frs");
if (!$frs) {
    response(422);
}

$group_id = @$postdata['groupId'];
if (!$group_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}
if (!$households->groupBelongsToSubscriber($group_id, $subscriber_id)) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}

$face_id = @(int)$postdata['faceId'];
if (!$face_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'faceId'));
}
if (!$frs->faceBelongsToSubscriberFrs($face_id, $subscriber_id)) {
    $flat_id = $households->getFlatIdBySubscriberGroupId($group_id);
    if (!isset($flat_id))
        response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
    if (!$frs->faceIdBelongsToFlatIdFrs($face_id, $flat_id))
        response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'faceId'));

    $flat_owner = false;
    foreach ($subscriber['flats'] as $flat) {
        if ($flat['flatId'] == $flat_id) {
            $flat_owner = ($flat['role'] == 0);
            break;
        }
    }
    if (!$flat_owner)
        response(403, false, i18n("mobile.404"));
}

$r = $frs->attachFaceToGroupFrs($face_id, $group_id, $subscriber_id);
if ($r === true) {
    response(204);
} else {
    response(417 , false, i18n("mobile.error"), i18n("mobile.cantAttachFaceToGroup"));
}

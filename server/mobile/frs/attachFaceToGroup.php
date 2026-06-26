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

$face_id = @(int)$postdata['faceId'];
if (!$face_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'faceId'));
}
if (!$frs->faceBelongsToSubscriberFrs($face_id, $subscriber_id)) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'faceId'));
}

$group_id = @$postdata['groupId'];
if (!$group_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}
if (!$households->groupBelongsToSubscriber($group_id, $subscriber_id)) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}

$r = $frs->attachFaceToGroupFrs($face_id, $group_id, $subscriber_id);
if ($r === true) {
    response(204);
} else {
    response(417 , false, i18n("mobile.error"), i18n("mobile.cantAttachFaceToGroup"));
}

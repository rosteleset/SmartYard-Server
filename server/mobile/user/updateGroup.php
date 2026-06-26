<?php

/**
 * @api {post} /mobile/user/updateGroup update subscriber's group
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup User
 *
 * @apiHeader {string} authorization authorization token
 *
 * @apiBody {integer} groupId group identifier
 * @apiBody {integer} flatId flat identifier
 * @apiBody {string} groupName name of the group
 *
 * @apiErrorExample Errors
 * 417 Failed to change the group
 * 422 Invalid parameter
 * 500 Internal server error
 */

auth();

$flat_id = (int)@$postdata['flatId'];
if (!$flat_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'flatId'));
}

$flat_ids = array_map(function($item) { return $item['flatId']; }, $subscriber['flats']);
$f = in_array($flat_id, $flat_ids);
if (!$f) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'flatId'));
}

$group_id = @$postdata['groupId'];
if (!$group_id) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}

$subscriber_id = (int)$subscriber['subscriberId'];
$households = loadBackend("households");
if (!$households->groupBelongsToSubscriber($group_id, $subscriber_id)) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupId'));
}

$group_name = @$postdata['groupName'];
if (!$group_name) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupName'));
}

$r = $households->updateSubscriberGroup($subscriber_id, $group_id, $flat_id, $group_name);
if ($r === true) {
    response(204);
} else {
    response(417 , false, i18n("mobile.error"), i18n("mobile.cantChangeGroup"));
}

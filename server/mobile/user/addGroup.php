<?php

/**
 * @api {post} /mobile/user/addGroup add a new subscriber's group
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup User
 *
 * @apiHeader {string} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 * @apiBody {string} groupName name of the group
 *
 * @apiSuccess {integer} groupId new group identifier
 *
 * @apiErrorExample Errors
 * 417 Failed to create a group
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

$group_name = @$postdata['groupName'];
if (!$group_name) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'groupName'));
}

$subscriber_id = (int)$subscriber['subscriberId'];

$households = loadBackend("households");
$group_id = $households->addSubscriberGroup($subscriber_id, $flat_id, $group_name);
if ($group_id > 0) {
    response(200, ['groupId' => $group_id]);
} else {
    response(417 , false, i18n("mobile.error"), i18n("mobile.cantCreateGroup"));
}

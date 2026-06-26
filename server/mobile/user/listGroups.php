<?php

/**
 * @api {post} /mobile/user/listGroups get subscriber's group list
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup User
 *
 * @apiHeader {string} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 *
 * @apiSuccess {object[]} - array of objects
 * @apiSuccess {integer} -.groupId group identifier
 * @apiSuccess {string} -.groupName name of the group
 *
 * @apiErrorExample Errors
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

$subscriber_id = (int)$subscriber['subscriberId'];

$households = loadBackend("households");
$r = $households->getSubscriberGroups($subscriber_id, $flat_id);
if (is_array($r) && count($r) > 0) {
    response(200, $r);
} else {
    response();
}

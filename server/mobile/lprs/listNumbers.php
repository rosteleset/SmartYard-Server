<?php

/**
 * @api {post} /mobile/lprs/listNumbers list license plate numbers
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup LPRS
 *
 * @apiHeader {String} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 *
 * @apiSuccess {String[]} - list of the license plate numbers
 */

auth();

// check if subscriber has access to the flat
$flat_id = (int)@$postdata['flatId'];
if (!$flat_id) {
    response(422);
}
$flatIds = array_map( function($item) { return $item['flatId']; }, $subscriber['flats']);
$f = in_array($flat_id, $flatIds);
if (!$f) {
    response(404, false, i18n("mobile.404"));
}

$households = loadBackend("households");
$numbers = array_filter(explode("\n", $households->getFlat($flat_id)['cars']));

if (count($numbers) > 0) {
    response(200, $numbers);
} else {
    response();
}

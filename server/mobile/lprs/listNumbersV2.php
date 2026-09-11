<?php

/**
 * @api {post} /mobile/lprs/listNumbersV2 list license plate numbers with country code
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup LPRS
 *
 * @apiHeader {String} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 *
 * @apiSuccess {Object[]} - list of the license plate numbers with country code
 * @apiSuccess {String} -.plateNumber license plate number
 * @apiSuccess {String} -.countryCode two-letter country code in lowercase
 * @apiSuccess {String} [-.validTo] expiration date/time (ISO 8601)
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
$numbers = $households->getFlatPlateNumbersV2($flat_id);

if ($numbers && count($numbers) > 0) {
    response(200, array_values($numbers));
} else {
    response();
}

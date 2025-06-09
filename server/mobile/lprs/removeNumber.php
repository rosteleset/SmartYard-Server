<?php

/**
 * @api {post} /mobile/lprs/removeNumber remove license plate number
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup LPRS
 *
 * @apiHeader {String} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 * @apiBody {String} number license plate number
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

// check number parameter
$number = (string)@$postdata['number'];
if (!$number) {
    response(422);
}

$number = trim($number);

// convert and validate license plate number
require_once __DIR__ . "/helpers/converters.php";
$number = toLatin($number);
if (!isValidPlateNumber($number)) {
    response(422, false, i18n("mobile.invalidPlateNumber"));
}

$households = loadBackend("households");
$numbers = array_filter(explode("\n", $households->getFlat($flat_id)['cars']));
$numbers = array_diff($numbers, [$number]);
$params = ["cars" => implode("\n", $numbers)];
$households->modifyFlat($flat_id, $params);

response(204);

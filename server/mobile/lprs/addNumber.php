<?php

/**
 * @api {post} /mobile/lprs/addNumber add license plate number
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup LPRS
 *
 * @apiHeader {String} authorization authorization token
 *
 * @apiBody {integer} flatId flat identifier
 * @apiBody {String} number license plate number
 * @apiBody {String} [countryCode="ru"] two-letter country code in lowercase
 * @apiBody {String} [validTo] expiration date/time (ISO 8601)
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
$country_code = strtolower(trim((string)(@$postdata['countryCode'] ?? 'ru'))) ?: 'ru';

// convert and validate license plate number
require_once __DIR__ . "/helpers/converters.php";
$number = toLatin($number);
if (!isValidPlateNumber($number, $country_code)) {
    response(422, false, i18n("mobile.invalidPlateNumber"));
}

$valid_to = @$postdata['validTo'] ?? @$postdata['valid_to'] ?? null;
if ($valid_to !== null && trim((string)$valid_to) === '') {
    $valid_to = null;
}

$households = loadBackend("households");
$households->addFlatPlateNumber($flat_id, $number, $valid_to, $country_code);

response(204);

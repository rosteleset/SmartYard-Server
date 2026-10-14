<?php

/**
 * @api {post} /mobile/lprs/listSupportedPlateNumberFormats List supported license plate number formats
 * @apiVersion 1.0.0
 * @apiDescription Returns the list of supported license plate number formats.
 *
 * @apiGroup LPRS
 *
 * @apiHeader {String} authorization Authorization token
 *
 * @apiSuccess {Object[]} - List of supported license plate number formats
 * @apiSuccess {String} -.countryCode Two-letter country code in lowercase
 * @apiSuccess {String} -.regex Regular expression used to validate license plate numbers
 */

auth();


$frs = loadBackend("frs");
$result = $frs->getSupportedPlateNumberFormats();

if ($result && count($result) > 0) {
    response(200, $result);
} else {
    response();
}

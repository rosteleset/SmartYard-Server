<?php

/**
 * @api {post} /mobile/frs/clusterFaces cluster faces by similarity
 * @apiVersion 1.0.0
 * @apiDescription **ready**
 *
 * @apiGroup FRS
 *
 * @apiHeader {string} authorization authorization token
 *
 * @apiBody {string[]} faces array of face identifiers
 * @apiBody {integer} flatId flat identifier
 * @apiBody {string} prefixName prefix used to generate group names (e.g. "group" → "group 1", "group 2").
 *
 * @apiErrorExample Errors
 * 417 Failed to create face clusters
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

$faces = @$postdata['faces'];
if (!is_array($faces) || empty($faces)) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'faces'));
}

$prefix_name = @$postdata['prefixName'];
if (!$prefix_name) {
    response(422, false, i18n("mobile.error"), i18n("mobile.invalidParameter", 'prefixName'));
}

$subscriber_id = (int)$subscriber['subscriberId'];

$frs = loadBackend("frs");
if (!$frs) {
    response(422);
}

$r = $frs->clusterFacesBySimilarityFrs($faces, $prefix_name, $subscriber_id, $flat_id);
if ($r === true) {
    response(204);
} else {
    response(417 , false, i18n("mobile.error"), i18n("mobile.cantClusterFaces"));
}

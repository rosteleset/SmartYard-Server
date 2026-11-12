<?php

/**
 * @api {post} /mobile/user/getBleKeys Get the subscriber's BLE keys
 * @apiVersion 1.0.0
 * @apiDescription Returns existing BLE keys. If no keys are available, returns 204 with no response body.
 *
 * @apiGroup User
 *
 * @apiHeader {String} authorization Authorization token
 *
 * @apiSuccess {Object[]} data BLE keys
 * @apiSuccess {String} data.key Key value
 *
 * @apiErrorExample Errors
 * 401 Unauthorized
 * 403 Authorization required
 * 404 Subscriber not found
 * 422 Missing Bearer token
 * 500 Internal server error
 */

auth();

if (!$subscriber) {
    response(404);
}

if (!isset($config["backends"]["ble"])) {
    response();
}

try {
    $ble = loadBackend("ble");
    if (!$ble) {
        response(500);
    }

    $keys = $ble->getKeys($subscriber["subscriberId"]);
} catch (Throwable $e) {
    error_log("BLE backend: " . $e->getMessage());
    response(500);
}

if ($keys) {
    response(200, $keys);
}

response();

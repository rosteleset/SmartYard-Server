<?php

/**
 * @api {post} /api/subscribers/bleKey/:subscriberId generate BLE key
 *
 * @apiVersion 1.0.0
 * @apiName generateBleKey
 * @apiGroup subscribers
 * @apiDescription Generates and assigns a BLE key to the subscriber according to the backend's issuance policy.
 *
 * @apiHeader {String} Authorization authentication token
 * @apiParam {Number} subscriberId Subscriber ID.
 *
 * @apiSuccess {String} key Key value.
 *
 * @apiError (400) badRequest Invalid subscriber ID.
 * @apiError (404) notFound BLE backend is unavailable.
 */

/**
 * @api {delete} /api/subscribers/bleKey/:subscriberId delete BLE key
 *
 * @apiVersion 1.0.0
 * @apiName deleteBleKey
 * @apiGroup subscribers
 * @apiDescription Deletes the specified key only for this subscriber. Missing keys are ignored.
 *
 * @apiHeader {String} Authorization authentication token
 * @apiParam {Number} subscriberId Subscriber ID.
 * @apiBody {String} key Key value to delete.
 *
 * @apiSuccessExample {json} Success:
 *     HTTP/1.1 204 No Content
 *
 * @apiError (400) badRequest Invalid subscriber ID or key.
 * @apiError (404) notFound BLE backend is unavailable.
 */

namespace api\subscribers;

use api\api;

/**
 * Subscriber BLE key management.
 */
class bleKey extends api
{
    public static function POST($params)
    {
        $subscriberId = $params["_id"] ?? null;
        if ((!is_int($subscriberId) && !is_string($subscriberId)) || !checkInt($subscriberId) || $subscriberId <= 0) {
            return api::ERROR("badRequest");
        }

        $ble = loadBackend("ble");
        if (!$ble) {
            return api::ERROR("notFound");
        }

        $key = $ble->generateKey($subscriberId);

        return api::ANSWER($key, "__asis__");
    }

    public static function DELETE($params)
    {
        $subscriberId = $params["_id"] ?? null;
        if ((!is_int($subscriberId) && !is_string($subscriberId)) || !checkInt($subscriberId) || $subscriberId <= 0) {
            return api::ERROR("badRequest");
        }

        $key = $params["key"] ?? null;
        if (!is_string($key) || $key === "") {
            return api::ERROR("badRequest");
        }

        $ble = loadBackend("ble");
        if (!$ble) {
            return api::ERROR("notFound");
        }

        $ble->deleteKey($subscriberId, $key);

        return [
            "204" => null,
        ];
    }

    public static function index(): array|bool
    {
        if (loadBackend("ble")) {
            return [
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        } else {
            return false;
        }
    }
}

<?php

/**
 * @api {get} /api/subscribers/bleKeys/:subscriberId get BLE keys
 *
 * @apiVersion 1.0.0
 * @apiName getBleKeys
 * @apiGroup subscribers
 * @apiDescription Returns the subscriber's existing BLE keys without generating new ones.
 *
 * @apiHeader {String} Authorization authentication token
 * @apiParam {Number} subscriberId Subscriber ID.
 *
 * @apiSuccess {Object[]} keys BLE keys, or an empty array if none are assigned.
 * @apiSuccess {String} keys.key Key value.
 *
 * @apiError (400) badRequest Invalid subscriber ID.
 * @apiError (404) notFound BLE backend is unavailable.
 */

namespace api\subscribers;

use api\api;

/**
 * Subscriber BLE keys.
 */
class bleKeys extends api
{
    public static function GET($params)
    {
        $subscriberId = $params["_id"] ?? null;
        if ((!is_int($subscriberId) && !is_string($subscriberId)) || !checkInt($subscriberId) || $subscriberId <= 0) {
            return api::ERROR("badRequest");
        }

        $ble = loadBackend("ble");
        if (!$ble) {
            return api::ERROR("notFound");
        }

        $keys = $ble->getKeys($subscriberId);

        return api::ANSWER($keys, "keys");
    }

    public static function index(): array|bool
    {
        if (loadBackend("ble")) {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        } else {
            return false;
        }
    }
}

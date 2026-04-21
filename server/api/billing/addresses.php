<?php

    /**
     * @api {post} /api/billing/addresses import address hierarchy from billing
     *
     * @apiVersion 1.0.0
     *
     * @apiName addresses
     * @apiGroup billing
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Object[]} addresses address hierarchy items imported from billing
     * @apiParam {String} addresses.regionUuid region UUID
     * @apiParam {String} addresses.region region name
     * @apiParam {String} [addresses.areaUuid] area UUID
     * @apiParam {String} [addresses.area] area name
     * @apiParam {String} [addresses.cityUuid] city UUID
     * @apiParam {String} [addresses.city] city name
     * @apiParam {String} [addresses.settlementUuid] settlement UUID
     * @apiParam {String} [addresses.settlement] settlement name
     * @apiParam {String} [addresses.streetUuid] street UUID
     * @apiParam {String} [addresses.street] street name
     * @apiParam {String} addresses.houseUuid house UUID
     * @apiParam {String} addresses.house house number/name
     * @apiParam {String[]} [addresses.services] available service identifiers for the house
     * @apiParam {Object[]} [addresses.flats] explicit list of flats to create
     * @apiParam {String} [addresses.flats.flatNumber] flat number
     * @apiParam {Object[]} [addresses.flatRanges] numeric flat ranges to create
     * @apiParam {Number|String} [addresses.flatRanges.fromFlat] start of flat range
     * @apiParam {Number|String} [addresses.flatRanges.toFlat] end of flat range
     * @apiSuccess {Object} addresses import result
     */

    namespace api\billing {

        use api\api;

        class addresses extends api {

            public static function POST($params) {
                if (!array_key_exists("addresses", $params) || !is_array($params["addresses"])) {
                    return api::ANSWER(false, "badRequest");
                }

                $billing = loadBackend("billing");
                if (!$billing) {
                    return api::ANSWER(false);
                }

                $response = $billing->importAddressHierarchy($params["addresses"]);

                return api::ANSWER($response, ($response !== false) ? "addresses" : false);
            }
        }
    }

<?php

    /**
     * @api {get} /api/subscribers/keys get rfIds
     *
     * @apiVersion 1.0.0
     *
     * @apiName getKeysBy
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String="flatId,rfId,keyId,domophoneId"} by
     * @apiQuery {String} query
     *
     * @apiSuccess {Object[]} keys
     */

    /**
     * @api {get} /api/subscribers/keys get rfIds
     *
     * @apiVersion 1.0.0
     *
     * @apiName getKeys
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {Number} by 0 - universal, 1 - subscriber, 2 - flat, 3 - entrance, 4 - house, 5 - company
     * @apiQuery {Number} query
     *
     * @apiSuccess {Object[]} keys
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * keys method
         */

        class keys extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $keys = $households->getKeys(@$params["by"], @$params["query"]);

                return api::ANSWER($keys, ($keys !== false) ? "keys" : false);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

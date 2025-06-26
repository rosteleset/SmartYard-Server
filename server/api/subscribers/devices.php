<?php

    /**
     * @api {get} /api/subscribers/devices get devices
     *
     * @apiVersion 1.0.0
     *
     * @apiName devices
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String="flat,subscriber,id,deviceToken,authToken"} by
     * @apiQuery {String} query
     *
     * @apiSuccess {Object[]} devices
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * devices method
         */

        class devices extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $devices = $households->getDevices(@$params["by"], @$params["query"]);

                return api::ANSWER($devices, $devices ? "devices" : false);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

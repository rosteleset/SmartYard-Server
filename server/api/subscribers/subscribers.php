<?php

    /**
     * @api {get} /subscribers/subscribers get subscribers by flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName subscribersByFlat
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} by flatId
     * @apiBody {Number} query
     *
     * @apiSuccess {Object[]} flat
     */

    /**
     * @api {get} /subscribers/subscribers get subscribers by id
     *
     * @apiVersion 1.0.0
     *
     * @apiName subscribersById
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} by subscriberId
     * @apiBody {Number} query
     *
     * @apiSuccess {Object[]} subscribers
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * subscribers method
         */

        class subscribers extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                switch (@$params["by"]) {
                    case "flatId":
                        $flat = [
                            "subscribers" => $households->getSubscribers("flatId", @$params["query"]),
                            "cameras" => $households->getCameras("flatId", @$params["query"]),
                            "keys" => $households->getKeys("flatId", @$params["query"]),
                        ];

                        return api::ANSWER($flat, $flat ? "flat" : false);

                    case "subscriberId":
                        $subscribers = $households->getSubscribers("id", @$params["query"]);
                        return api::ANSWER($subscribers, $subscribers ? "subscribers" : false);
                }

                return api::ERROR();
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

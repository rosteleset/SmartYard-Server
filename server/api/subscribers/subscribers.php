<?php

    /**
     * @api {get} /api/subscribers/subscribers get subscribers by
     *
     * @apiVersion 1.0.0
     *
     * @apiName subscribersBy
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String="flatId","houseId","subscriberId"} by
     * @apiQuery {Number} query
     *
     * @apiSuccess {Object[]} result result is "flat" for flatId or "subscribers" for others
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

                    case "houseId":
                        $subscribers = $households->getSubscribers("houseId", @$params["query"]);
                        return api::ANSWER($subscribers, $subscribers ? "subscribers" : false);

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

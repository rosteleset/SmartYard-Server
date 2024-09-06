<?php

    /**
     * @api {get} /api/subscribers/search search subscribers
     *
     * @apiVersion 1.0.0
     *
     * @apiName search
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String} search
     *
     * @apiSuccess {Object[]} subscribers
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * search method
         */

        class search extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $result = $households->searchSubscriber(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "subscribers" : false);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

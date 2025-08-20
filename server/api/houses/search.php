<?php

    /**
     * @api {get} /api/houses/search search houses
     *
     * @apiVersion 1.0.0
     *
     * @apiName searchHouses
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} search
     *
     * @apiSuccess {Object[]} houses
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * search method
         */

        class search extends api {

            public static function GET($params) {
                $addresses = loadBackend("addresses");

                $result = $addresses->searchHouse(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "houses" : false);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

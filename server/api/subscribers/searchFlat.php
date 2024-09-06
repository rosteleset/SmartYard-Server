<?php

    /**
     * @api {get} /api/subscribers/search search subscribers
     *
     * @apiVersion 1.0.0
     *
     * @apiName searchFlat
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiQuery {String} search
     *
     * @apiSuccess {Object[]} flats
     */

    /**
     * subscribers api
     */

    namespace api\subscribers
    {

        use api\api;

        /**
         * searchFlat method
         */

        class searchFlat extends api
        {

            public static function GET($params)
            {
                $households = loadBackend("households");

                $result = $households->searchFlat(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "flats" : false);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

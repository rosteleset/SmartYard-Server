<?php

    /**
     * @api {get} /api/subscribers/search search keys
     *
     * @apiVersion 1.0.0
     *
     * @apiName searchRf
     * @apiGroup subscribers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} search
     *
     * @apiSuccess {Object[]} rfs
     */

    /**
     * subscribers api
     */

    namespace api\subscribers
    {

        use api\api;

        /**
         * searchRf method
         */

        class searchRf extends api
        {

            public static function GET($params)
            {
                $households = loadBackend("households");

                $result = $households->searchRf(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "rfs" : false);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

<?php

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

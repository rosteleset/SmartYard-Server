<?php

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

<?php

    /**
     * subscribers api
     */

    namespace api\subscribers
    {

        use api\api;

        /**
         * search method
         */

        class search extends api
        {

            public static function GET($params)
            {
                $households = loadBackend("households");

                $result = $households->searchSubscriber(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "subscribers" : false);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

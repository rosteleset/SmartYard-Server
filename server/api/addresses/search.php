<?php

    /**
     * addresses api
     */

    namespace api\addresses
    {

        use api\api;

        /**
         * search method
         */

        class search extends api
        {

            public static function GET($params)
            {
                $addresses = loadBackend("addresses");

                $result = $addresses->searchAddress(@$params["search"]);

                return api::ANSWER($result, ($result !== false) ? "addresses" : false);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

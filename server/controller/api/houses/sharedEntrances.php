<?php

    /**
     * addresses api
     */

    namespace api\houses
    {

        use api\api;

        /**
         * house method
         */

        class sharedEntrances extends api
        {

            public static function GET($params)
            {
                $households = loadBackend("households");

                $entrances = $households->getSharedEntrances(@$params["_id"]);

                return api::ANSWER($entrances, ($entrances !== false)?"entrances":"notAcceptable");
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)"
                ];
            }
        }
    }

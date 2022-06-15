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
                $houses = loadBackend("houses");

                $entrances = $houses->getSharedEntrances(@$params["_id"]);

                return api::ANSWER($entrances, ($entrances !== false)?"entrances":"notAcceptable");
            }

            public static function index()
            {
                return [
                    // !!! only one level is supported !!!
                    "GET" => "#same(houses,house,GET)"
                ];
            }
        }
    }

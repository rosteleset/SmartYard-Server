<?php

    /**
     * houses api
     */

    namespace api\houses
    {

        use api\api;

        /**
         * house method
         */

        class cms extends api
        {

            public static function GET($params)
            {
                $houses = loadBackend("houses");

                $cms = $houses->getCms($params["_id"]);

                return api::ANSWER($cms, ($cms !== false)?"flatId":false);
            }

            public static function PUT($params)
            {
                $houses = loadBackend("houses");

                $success = $houses->getCms($params["_id"], $params["cms"]);

                return api::ANSWER($success);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(houses,house,GET)",
                    "PUT" => "#same(houses,house,PUT)",
                ];
            }
        }
    }

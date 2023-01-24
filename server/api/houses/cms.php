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
                $households = loadBackend("households");

                $cms = $households->getCms($params["_id"]);

                return api::ANSWER($cms, ($cms !== false)?"cms":false);
            }

            public static function PUT($params)
            {
                $households = loadBackend("households");

                $success = $households->setCms($params["_id"], $params["cms"]);

                return api::ANSWER($success);
            }

            public static function index()
            {
                return [
                    "GET" => "#same(addresses,house,GET)",
                    "PUT" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

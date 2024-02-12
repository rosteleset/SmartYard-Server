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

        class autoconfigure extends api
        {

            public static function POST($params)
            {
                $households = loadBackend("households");

                $success = false;

                switch (@$params["object"]) {
                    case "domophone":
                        $success = $households->autoconfigureDomophone($params["_id"], @$params["firstTime"]);
                        break;
                }

                return api::ANSWER($success);
            }

            public static function index()
            {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

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

        class flat extends api
        {

            public static function POST($params)
            {
                $households = loadBackend("households");

                $flatId = $households->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["manualBlock"], $params["adminBlock"], $params["openCode"], $params["plog"], $params["autoOpen"], $params["whiteRabbit"], $params["sipEnabled"], $params["sipPassword"]);

                return api::ANSWER($flatId, ($flatId !== false)?"flatId":"notAcceptable");
            }

            public static function PUT($params)
            {
                $households = loadBackend("households");

                $success = $households->modifyFlat($params["_id"], $params);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params)
            {
                $households = loadBackend("households");

                $success = $households->deleteFlat($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index()
            {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                    "PUT" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

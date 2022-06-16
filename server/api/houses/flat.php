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
                $houses = loadBackend("houses");

                $flatId = $houses->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["entrances"], $params["apartmentsAndLevels"]);

                return api::ANSWER($flatId, ($flatId !== false)?"flatId":"notAcceptable");
            }

            public static function PUT($params)
            {
                $houses = loadBackend("houses");

                $success = $houses->modifyFlat($params["_id"], $params["floor"], $params["flat"], $params["entrances"], $params["apartmentsAndLevels"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params)
            {
                $houses = loadBackend("houses");

                $success = $houses->deleteFlat($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index()
            {
                return [
                    "POST" => "#same(houses,house,PUT)",
                    "PUT" => "#same(houses,house,PUT)",
                    "DELETE" => "#same(houses,house,PUT)",
                ];
            }
        }
    }

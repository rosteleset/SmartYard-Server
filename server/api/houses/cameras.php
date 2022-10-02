<?php

    /**
     * houses api
     */

    namespace api\houses
    {

        use api\api;

        /**
         * entrance method
         */

        class cameras extends api
        {

            public static function POST($params)
            {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("house", $params["houseId"], $params["cameraId"], $params["common"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":"notAcceptable");
            }

            public static function DELETE($params)
            {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("house", $params["houseId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index()
            {
                return [
                    "POST" => "#same(houses,house,PUT)",
                    "DELETE" => "#same(houses,house,PUT)",
                ];
            }
        }
    }

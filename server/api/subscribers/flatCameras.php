<?php

    /**
     * subscribers api
     */

    namespace api\subscribers
    {

        use api\api;

        /**
         * flatCameras method
         */

        class flatCameras extends api
        {

            public static function POST($params)
            {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("flat", $params["flatId"], $params["cameraId"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":"notAcceptable");
            }

            public static function DELETE($params)
            {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("flat", $params["flatId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index()
            {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

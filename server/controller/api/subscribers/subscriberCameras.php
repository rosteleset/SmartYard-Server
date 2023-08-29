<?php

    /**
     * subscribers api
     */

    namespace api\subscribers
    {

        use api\api;

        /**
         * suvscriberCameras method
         */

        class subscriberCameras extends api
        {

            public static function POST($params)
            {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":"notAcceptable");
            }

            public static function DELETE($params)
            {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index()
            {
                return [
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

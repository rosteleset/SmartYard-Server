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

        class entrance extends api
        {


            public static function POST($params)
            {
                $households = loadBackend("households");

                if (@$params["entranceId"]) {
                    $success = $households->addEntrance($params["houseId"], $params["entranceId"], $params["prefix"]);

                    return api::ANSWER($success);
                } else {
                    $entranceId = $households->createEntrance($params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["cmsLevels"]);

                    return api::ANSWER($entranceId, ($entranceId !== false)?"entranceId":false);
                }
            }

            public static function PUT($params)
            {
                $households = loadBackend("households");

                $success = $households->modifyEntrance($params["_id"], $params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["cmsLevels"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params)
            {
                $households = loadBackend("households");

                if (@$params["houseId"]) {
                    $success = $households->deleteEntrance($params["_id"], $params["houseId"]);
                } else {
                    $success = $households->destroyEntrance($params["_id"]);
                }

                return api::ANSWER($success);
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

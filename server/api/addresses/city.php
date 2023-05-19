<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * city method
         */

        class city extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyCity($params["_id"], $params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $cityId = $addresses->addCity($params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

                return api::ANSWER($cityId, ($cityId !== false)?"cityId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteCity($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

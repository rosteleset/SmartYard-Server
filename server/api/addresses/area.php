<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * area method
         */

        class area extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyArea($params["_id"], $params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $areaId = $addresses->addArea($params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

                return api::ANSWER($areaId, ($areaId !== false)?"areaId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteArea($params["_id"]);

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

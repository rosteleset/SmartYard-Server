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

                $success = $addresses->modifyCity($params["_id"], $params["regionId"], $params["areaId"], $params["areaFiasId"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $areaId = $addresses->addCity($params["regionId"], $params["areaFiasId"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"]);

                return api::ANSWER($areaId, ($areaId !== false)?"areaId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteCity($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            }
        }
    }

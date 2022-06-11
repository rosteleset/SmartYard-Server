<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * region method
         */

        class region extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyRegion($params["_id"], $params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $regionId = $addresses->addRegion($params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"]);

                return api::ANSWER($regionId, ($regionId !== false)?"regionId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteRegion($params["_id"]);

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

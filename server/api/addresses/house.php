<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * house method
         */

        class house extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["housetUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $houseId = $addresses->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

                return api::ANSWER($houseId, ($houseId !== false)?"houseId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteHouse($params["_id"]);

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

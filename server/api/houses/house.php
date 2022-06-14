<?php

    /**
     * addresses api
     */

    namespace api\houses {

        use api\api;

        /**
         * house method
         */

        class house extends api {

            public static function GET($params) {
                $houses = loadBackend("houses");

                $house = $houses->getHouse($params["_id"]);

                return api::ANSWER($house, ($house !== false)?"house":"notFound");
            }

            public static function PUT($params) {
                $houses = loadBackend("houses");

                $success = $houses->modifyHouse($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "GET",
                    "PUT",
                ];
            }
        }
    }

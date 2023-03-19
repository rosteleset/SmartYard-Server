<?php

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs cell
         */

        class cell extends api {

            public static function GET($params) {
                $cs = loadBackend("cs");

                $sheet = false;

                return api::ANSWER($sheet, ($sheet !== false)?"sheet":"notFound");
            }

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "PUT",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

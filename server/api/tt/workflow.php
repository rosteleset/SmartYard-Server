<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflow method
         */

        class workflow extends api {

            public static function PUT($params) {
                $success = loadBackend("tt")->setWorkflowAlias($params["workflow"], $params["alias"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

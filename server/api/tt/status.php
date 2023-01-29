<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * status method
         */

        class status extends api {

            public static function PUT($params) {
                $success = loadBackend("tt")->moodifyStatus($params["_id"], $params["statusDisplay"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT" => "#same(tt,project,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

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

            public static function POST($params) {
                $statusId = loadBackend("tt")->addStatus($params["status"]);

                return api::ANSWER($statusId, ($statusId !== false)?"statusId":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyStatus($params["_id"], $params["status"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteStatus($params["_id"]);

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

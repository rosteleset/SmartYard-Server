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

            public static function GET($params) {
                $workflow = loadBackend("tt")->getWorkflow($params["_id"]);

                if ($workflow !== false) {
                    return api::ANSWER($workflow, "body");
                } else {
                    return api::ERROR("inaccessible");
                }
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putWorkflow($params["_id"], $params["body"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteWorkflow($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

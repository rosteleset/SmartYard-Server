<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customWorkflow method
         */

        class customWorkflow extends api {

            public static function GET($params) {
                $workflow = loadBackend("tt")->getWorkflow($params["workflow"]);

                if ($workflow) {
                    return api::ANSWER($workflow, "body");
                } else {
                    return api::ERROR("inaccessible");
                }
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putWorkflow($params["workflow"], $params["body"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteWorkflow($params["workflow"], $params["body"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

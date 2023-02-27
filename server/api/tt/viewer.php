<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * viewer method
         */

        class viewer extends api {

            public static function GET($params) {
                $success = loadBackend("tt")->getViewers();

                return api::ANSWER($success, ($success !== false)?"viewers":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putViewer($params['field'], $params["name"], $params['code']);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteViewer($params['field'], $params['name']);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                        "POST" => "#same(tt,project,POST)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

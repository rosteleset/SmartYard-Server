<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customFilter method
         */

        class customFilter extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                $filter = false;

                if ($tt) {
                    if (@$params["_id"] && @$params["_login"]) {
                        $filter = $tt->getFilter($params["_id"], $params["_login"]);
                    }
                }

                return api::ANSWER($filter, ($filter !== false)?"body":"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putFilter($params["_id"], $params["body"], $params["_login"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFilter($params["_id"], $params["_login"]);

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

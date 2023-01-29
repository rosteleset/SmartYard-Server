<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * filterAvailable method
         */

        class filterAvailable extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return false;
                }

                return api::ANSWER($tt->filterAvailable($params["_id"]), "available");
            }

            public static function POST($params) {
                $success = loadBackend("tt")->addFilterAvailable($params["_id"], $params["uid"], $params["gid"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFilterAvailable($params["_id"], $params["uid"], $params["gid"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                        "POST" => "#same(tt,project,POST)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

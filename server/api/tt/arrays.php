<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class arrays extends api {

            public static function POST($params)
            {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success =  $tt->addArrayValue($params["_id"], $params["field"], $params["value"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params)
            {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success =  $tt->deleteArrayValue($params["_id"], $params["field"], $params["value"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

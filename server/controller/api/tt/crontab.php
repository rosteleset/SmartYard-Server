<?php

    /**
     * crontab api
     */

    namespace api\tt {

        use api\api;

        /**
         * filter method
         */

        class crontab extends api {

            public static function POST($params) {
                $success = loadBackend("tt")->addCrontab($params["crontab"], $params["projectId"], $params["filter"], $params["uid"], $params["action"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCrontab($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,project,POST)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

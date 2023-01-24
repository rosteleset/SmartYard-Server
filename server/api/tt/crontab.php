<?php

    /**
     * crontab api
     */

    namespace api\crontab {

        use api\api;

        /**
         * filter method
         */

        class crontab extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return false;
                }

                if (@$params["_id"]) {
                    return api::ANSWER($tt->getCrontab($params["_id"]), "crontab");
                } else {
                    return api::ANSWER($tt->getCrontabs(), "crontabs");
                }
            }

            public static function POST($params) {
                $success = loadBackend("tt")->addCrontab($params["crontab"], $params["filter"], $params["uid"], $params["action"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyCrontab($params["_id"], $params["filter"], $params["uid"], $params["action"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCrontab($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

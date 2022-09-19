<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customWorkflow method
         */

        class filter extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return false;
                }

                if (@$params["_id"]) {
                    return api::ANSWER($tt->getFilter($params["_id"]), "body");
                } else {
                    return api::ANSWER($tt->availableFilters(), "filters");
                }
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putFilter($params["_id"], $params["body"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFilter($params["_id"]);

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

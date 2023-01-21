<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class issue extends api {

            public static function GET($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function POST($params) {
                $tt = loadBackend("tt");

                $id = $tt->loadWorkflow($params["issue"]["workflow"])->createIssue($params["issue"]);

                return api::ANSWER($id, ($id !== false)?false:"notAcceptable");
            }

            public static function PUT($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function DELETE($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "tt",
                        "POST" => "tt",
                        "PUT" => "tt",
                        "DELETE" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

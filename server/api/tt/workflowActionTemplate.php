<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflowActionTemplate method
         */

        class workflowActionTemplate extends api {

            public static function GET($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

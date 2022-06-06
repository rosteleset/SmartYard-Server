<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * workflowProgressAction method
         */

        class workflowProgressAction extends api {

            public static function POST($params) {
//                $tt_resolutions = loadBackend("tt")->getResolutions;
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

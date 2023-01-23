<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt issues count and bodies
         */

        class issues extends api {

            public static function GET($params) {
                $issues = [];

                return api::ANSWER($issues, ($issues !== false)?false:"notAcceptable");
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

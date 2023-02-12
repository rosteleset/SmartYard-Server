<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class comment extends api {

            public static function GET($params) {
                return api::ANSWER();
            }

            public static function POST($params)
            {
                return api::ANSWER();
            }

            public static function PUT($params)
            {
                return api::ANSWER();
            }

            public static function DELETE($params)
            {
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "POST" => "#same(tt,issue,POST)",
                        "PUT" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

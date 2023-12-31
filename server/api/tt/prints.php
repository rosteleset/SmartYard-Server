<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * prints method
         */

        class prints extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch ($params["mode"]) {
                    case "data":
                        break;

                    case "formatter":
                        break;

                    case "template":
                        break;
    
                    case "result":
                        break;
                }

                return api::ANSWER($success);
            }

            public static function POST($params) {
                $tt = loadBackend("tt");

                return api::ANSWER($success);
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch ($params["mode"]) {
                    case "data":
                        break;

                    case "formatter":
                        break;

                    case "template":
                        break;
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");
                $success = false;

                switch ($params["mode"]) {
                    case "print":
                        break;

                    case "template":
                        break;
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "POST" => "#same(tt,project,POST)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

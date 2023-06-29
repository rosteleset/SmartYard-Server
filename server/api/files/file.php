<?php

    /**
     * files api
     */

    namespace api\files {

        use api\api;

        /**
         * file method
         */

        class file extends api {

            public static function GET($params) {
                $files = loadBackend("files");

                $file = false;

                if ($files) {
                    //
                }

                return api::ANSWER($file, ($file !== false)?"file":false);
            }

            public static function POST($params) {
                $files = loadBackend("files");

                $success = false;

                return api::ANSWER($success);
            }

            public static function PUT($params) {
                $files = loadBackend("files");

                $success = false;

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $files = loadBackend("files");

                $success = false;

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("files")) {
                    return [
                        "GET" => "#common",
                        "POST" => "#common",
                        "PUT" => "#common",
                        "DELETE" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

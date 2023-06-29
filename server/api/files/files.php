<?php

    /**
     * files api
     */

    namespace api\files {

        use api\api;

        /**
         * files method
         */

        class files extends api {

            public static function GET($params) {
                $files = loadBackend("files");

                $_files = [];

                if ($files) {
                    //
                }

                return api::ANSWER($_files, ($_files !== false)?"files":false);
            }

            public static function index() {
                if (loadBackend("files")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

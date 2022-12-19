<?php

    /**
     * configs api
     */

    namespace api\configs {

        use api\api;

        /**
         * configs method
         */

        class configs extends api {

            public static function GET($params) {
                $frs = loadBackend("frs");

                $sections = [
                    "FRSServers" => $frs->servers(),
                ];

                return api::ANSWER($sections, ($sections !== false)?"sections":false);
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

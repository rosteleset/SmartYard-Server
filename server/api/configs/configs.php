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
                $configs = loadBackend("configs");

                $sections = [
                    "FRSServers" => $configs->getFRSServers(),
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

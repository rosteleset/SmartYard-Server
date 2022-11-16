<?php

    /**
     * config api
     */

    namespace api\config {

        use api\api;

        /**
         * config method
         */

        class config extends api {

            public static function GET($params) {
                $config = loadBackend("config");

                if ($config) {
                    $sections = [
                        "FRSServers" => $config->getFRSServers(),
                    ];

                    return api::ANSWER($sections, "sections");
                } else {
                    return api::ERROR("inaccessible");
                }
            }

            public static function index() {
                if (loadBackend("config")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

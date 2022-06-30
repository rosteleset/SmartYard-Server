<?php

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * domophones method
         */

        class domophones extends api {

            public static function GET($params) {
                $houses = loadBackend("houses");

                $response = [
                    "domophones" => $houses->getDomophones(),
                    "models" => $houses->getModels(),
                    "servers" => $houses->getAsteriskServers(),
                ];

                return api::ANSWER($response, "domophones");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

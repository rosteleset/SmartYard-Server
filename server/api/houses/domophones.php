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
                $households = loadBackend("households");

                $response = [
                    "domophones" => $households->getDomophones(),
                    "models" => $households->getModels(),
                    "servers" => $households->getAsteriskServers(),
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

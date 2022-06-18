<?php

    /**
     * domophones api
     */

    namespace api\domophones {

        use api\api;

        /**
         * domophones method
         */

        class domophones extends api {

            public static function GET($params) {
                $domophones = loadBackend("domophones");

                $response = [
                    "domophones" => $domophones->getDomophones(),
                    "models" => $domophones->getModels(),
                    "cmses" => $domophones->getCMSes(),
                ];

                return api::ANSWER($response, ($response !== false)?"domophones":"badRequest");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

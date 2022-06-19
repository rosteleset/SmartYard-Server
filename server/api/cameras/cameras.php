<?php

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * cameras method
         */

        class cameras extends api {

            public static function GET($params) {
                $cameras = loadBackend("cameras");

                $response = [
                    "cameras" => $cameras->getCameras(),
                    "models" => $cameras->getModels(),
                ];

                return api::ANSWER($response, "cameras");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

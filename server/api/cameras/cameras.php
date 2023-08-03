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
                $configs = loadBackend("configs");
                $frs = loadBackend("frs");

                $response = [
                    "cameras" => $cameras->getCameras(),
                    "models" => $configs->getCamerasModels(),
                    "frsServers" => $frs?$frs->servers():[],
                ];

                return api::ANSWER($response, "cameras");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

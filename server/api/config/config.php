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
                $cameras = loadBackend("cameras");

                $cameraId = $cameras->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["publish"], $params["flussonic"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":false);
            }

            public static function index() {
                return [
                    "GET",
                ];
            }
        }
    }

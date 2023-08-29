<?php

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * camera method
         */

        class camera extends api {

            public static function POST($params) {
                $cameras = loadBackend("cameras");

                $cameraId = $cameras->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":false);
            }

            public static function PUT($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->modifyCamera($params["_id"], $params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["mdLeft"], $params["mdTop"], $params["mdWidth"], $params["mdHeight"], $params["common"], $params["comment"]);

                return api::ANSWER($success?:$params["_id"], $success?"cameraId":false);
            }

            public static function DELETE($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->deleteCamera($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT" => "#same(addresses,house,PUT)",
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

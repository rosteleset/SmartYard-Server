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

                $cameraId = $cameras->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["comment"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":false);
            }

            public static function PUT($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->modifyCamera($params["_id"], $params["enabled"], $params["model"], $params["url"], $params["stream"],  $params["credentials"], $params["comment"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->deleteCamera($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            }
        }
    }

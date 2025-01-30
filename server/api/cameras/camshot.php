<?php

    /**
     * @api {get} /api/cameras/camshot/:cameraId camshot
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCamshot
     * @apiGroup cameras
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam cameraId
     *
     * @apiSuccess {base64} image
     */

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * camera method
         */

        class camshot extends api {

            public static function GET($params) {
                $cameras = loadBackend("cameras");

                $cameras = $cameras->getCameras("id", $params["_id"]);

                $shot = false;

                if ($cameras && $cameras[0]) {
                    $camera = $cameras[0];

                    try {
                        $device = loadDevice('camera', $camera["model"], $camera["url"], $camera["credentials"]);
                        $shot = $device->getCamshot();
                        $shot = base64_encode($shot);
                    } catch (\Exception $e) {
                        error_log(print_r($e, true));
                    }
                }

                return api::ANSWER($shot, ($shot !== false) ? "shot" : false);
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

<?php

    /**
     * @api {get} /api/cameras/camera/:cameraId camshot
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

                $cameras = $cameras->getCamera("id", $params["_id"]);

                $show = false;

                if ($cameras && $cameras[0]) {
                    $camera = $camera[0];

                    try {
                        $device = loadDevice('camera', $camera["model"], $camera["url"], $camera["credentials"]);
                        $shot = $device->getCamshot();
                        $show = base64_encode($shot);
                    } catch (\Exception $e) {
                        //
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

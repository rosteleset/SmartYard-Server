<?php

    /**
     * @api {get} /api/cameras/camshot/:cameraId camshot
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCamshot
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
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
         * camshot method
         */

        class camshot extends api {

            public static function GET($params) {
                $camerasBackend = loadBackend("cameras");

                $cameras = $camerasBackend->getCameras("id", $params["_id"]);

                $shot = false;

                if ($cameras && $cameras[0]) {
                    $cameraId = $cameras[0]["cameraId"];
                    $shot = $camerasBackend->getSnapshot($cameraId);

                    if ($shot !== null) {
                        $shot = base64_encode($shot);
                    } else {
                        $shot = false;
                        error_log('Error getting snapshot for camera ' . $cameraId);
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

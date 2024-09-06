<?php

    /**
     * @api {post} /api/subscribers/flatCameras add camera to flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFlatCamera
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} flatId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Number} cameraId
     */

    /**
     * @api {delete} /api/subscribers/flatCameras delete camera from flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFlatCamera
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} flatId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * flatCameras method
         */

        class flatCameras extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("flat", $params["flatId"], $params["cameraId"]);

                return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : "notAcceptable");
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("flat", $params["flatId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

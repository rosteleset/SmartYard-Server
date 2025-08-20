<?php

    /**
     * @api {post} /api/houses/cameras add camera to house
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCamera
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} houseId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Number} cameraId
     */

    /**
     * @api {put} /api/houses/cameras set camera path
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyCamera
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} houseId
     * @apiBody {Number} cameraId
     * @apiBody {String} path
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/houses/cameras remove camera from house
     *
     * @apiVersion 1.0.0
     *
     * @apiName removeCamera
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} houseId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * cameras method
         */

        class cameras extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("house", $params["houseId"], $params["cameraId"]);

                return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyCamera("house", $params["houseId"], $params["cameraId"], $params["path"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("house", $params["houseId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#same(addresses,house,PUT)",
                    "PUT" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

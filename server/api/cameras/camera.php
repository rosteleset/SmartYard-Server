<?php

    /**
     * @api {post} /api/cameras/camera create camera
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCamera
     * @apiGroup cameras
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {Boolean} enabled
     * @apiBody {String} model
     * @apiBody {String} url
     * @apiBody {String} stream
     * @apiBody {String} credentials
     * @apiBody {String} name
     * @apiBody {String} dvrStream
     * @apiBody {String} timezone
     * @apiBody {Number} lat
     * @apiBody {Number} lon
     * @apiBody {Number} direction
     * @apiBody {Number} angle
     * @apiBody {Number} distance
     * @apiBody {String} frs
     * @apiBody {Number} frsMode
     * @apiBody {Number} mdLeft
     * @apiBody {Number} mdTop
     * @apiBody {Object} mdArea
     * @apiBody {Object} rcArea
     * @apiBody {Boolean} common
     * @apiBody {String} comments
     * @apiBody {Boolean} sound
     * @apiBody {Boolean} monitoring
     * @apiBody {Object} ext
     *
     * @apiSuccess {Number} cameraId
     */

    /**
     * @api {put} /api/cameras/camera/:cameraId modify camera
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyCamera
     * @apiGroup cameras
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} cameraId
     * @apiBody {Boolean} enabled
     * @apiBody {String} model
     * @apiBody {String} url
     * @apiBody {String} stream
     * @apiBody {String} credentials
     * @apiBody {String} name
     * @apiBody {String} dvrStream
     * @apiBody {String} timezone
     * @apiBody {Number} lat
     * @apiBody {Number} lon
     * @apiBody {Number} direction
     * @apiBody {Number} angle
     * @apiBody {Number} distance
     * @apiBody {String} frs
     * @apiBody {Number} frsMode
     * @apiBody {Number} mdArea
     * @apiBody {Number} rcArea
     * @apiBody {Boolean} common
     * @apiBody {String} comments
     * @apiBody {Boolean} sound
     * @apiBody {Boolean} monitoring
     * @apiBody {Object} ext
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/cameras/camera/:cameraId delete camera
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCamera
     * @apiGroup cameras
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} cameraId
     *
     * @apiSuccess {Boolean} operationResult
     */

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

                $cameraId = $cameras->addCamera($params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["frsMode"], $params["mdArea"], $params["rcArea"], $params["common"], $params["comments"], $params["sound"], $params["monitoring"], $params["ext"]);

                return api::ANSWER($cameraId, ($cameraId !== false)?"cameraId":false);
            }

            public static function PUT($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->modifyCamera($params["_id"], $params["enabled"], $params["model"], $params["url"], $params["stream"], $params["credentials"], $params["name"], $params["dvrStream"], $params["timezone"], $params["lat"], $params["lon"], $params["direction"], $params["angle"], $params["distance"], $params["frs"], $params["frsMode"], $params["mdArea"], $params["rcArea"], $params["common"], $params["comments"], $params['sound'], $params["monitoring"], $params["ext"]);

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

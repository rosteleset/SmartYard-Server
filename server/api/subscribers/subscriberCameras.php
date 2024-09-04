<?php

    /**
     * @api {post} /subscribers/subscriberCameras add camera to subscriber
     *
     * @apiVersion 1.0.0
     *
     * @apiName addSubscriberCamera
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} subscriberId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Number} cameraId
     */

    /**
     * @api {delete} /subscribers/subscriberCameras delete camera from subscriber
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteSubscriberCamera
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} subscriberId
     * @apiBody {Number} cameraId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * subscribers api
     */

    namespace api\subscriber {

        use api\api;

        /**
         * subscriberCameras method
         */

        class subscriberCameras extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $cameraId = $households->addCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

                return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : "notAcceptable");
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->unlinkCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#same(addresses,house,POST)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

<?php

    /**
     * @api {get} /api/cameras/parking/:cameraId get parking configuration
     *
     * @apiVersion 1.0.0
     *
     * @apiName getParking
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} cameraId
     *
     * @apiSuccess {Object} parking configuration with computed problems and readiness, null when there is no configuration
     * @apiSuccess {Object} settings { interval, minInterval } global processing interval and the least allowed override, seconds
     * @apiError notFound parking backend is not configured
     */

    /**
     * @api {put} /api/cameras/parking/:cameraId create or replace parking configuration
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyParking
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} cameraId
     * @apiBody {Number} revision revision the configuration is based on (0 for a new configuration)
     * @apiBody {Number} enabled
     * @apiBody {Number} [detectionImageSize=320] size the frame is resized to for vehicle detection, 160..1024
     * @apiBody {Number} [confidenceThreshold=0.5] minimum detection confidence, 0..1
     * @apiBody {Number} [processingInterval] processing interval override in seconds, null for the global one, at least minInterval
     * @apiBody {Number} frameWidth width of the frame used for markup, pixels
     * @apiBody {Number} frameHeight height of the frame used for markup, pixels
     * @apiBody {Object} calibration null or { points: [ { u, v, x, y } ], checks: [ { u1, v1, u2, v2, length } ] }
     * @apiBody {Object[]} areas [ { parkingAreaId, name, enabled, imagePoints: [ { u, v } x 4 ], baseEdge, spaceWidth, spaceLength, spaceAngle } ]
     *
     * @apiSuccess {Object} parking saved configuration
     * @apiError notFound camera not found or parking backend is not configured
     * @apiError parkingRevisionConflict the configuration was changed by somebody else
     * @apiError parkingInvalidConfig invalid configuration (the problem code is passed in X-Last-Error)
     */

    /**
     * @api {delete} /api/cameras/parking/:cameraId delete parking configuration
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteParking
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
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
         * parking method
         */

        class parking extends api {

            private static function failure($parking) {
                $failure = $parking->lastFailure();

                if (in_array($failure, [ "notFound", "parkingRevisionConflict", "parkingInvalidConfig" ])) {
                    return api::ERROR($failure);
                }

                return api::ERROR();
            }

            public static function GET($params) {
                $parking = loadBackend("parking");

                if (!$parking) {
                    return api::ERROR("notFound");
                }

                $config = $parking->getConfig($params["_id"]);

                if ($config === false) {
                    return self::failure($parking);
                }

                return api::SUCCESS("__asis__", [
                    "parking" => $config,
                    "settings" => $parking->settings(),
                ]);
            }

            public static function PUT($params) {
                $parking = loadBackend("parking");

                if (!$parking) {
                    return api::ERROR("notFound");
                }

                $config = [];

                foreach ([ "revision", "enabled", "detectionImageSize", "confidenceThreshold", "processingInterval", "frameWidth", "frameHeight", "calibration", "areas" ] as $key) {
                    if (array_key_exists($key, $params)) {
                        $config[$key] = $params[$key];
                    }
                }

                $config = $parking->saveConfig($params["_id"], $config);

                if ($config === false) {
                    return self::failure($parking);
                }

                return api::ANSWER($config, "parking");
            }

            public static function DELETE($params) {
                $parking = loadBackend("parking");

                if (!$parking) {
                    return api::ERROR("notFound");
                }

                $success = $parking->deleteConfig($params["_id"]);

                return $success ? api::ANSWER($success) : self::failure($parking);
            }

            public static function index() {
                if (!loadBackend("parking")) {
                    return false;
                }

                return [
                    "GET" => "#same(addresses,house,GET)",
                    "PUT" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

<?php

    /**
     * @api {get} /api/cameras/parkingResult/:cameraId get the latest parking occupancy result
     *
     * @apiVersion 1.0.0
     *
     * @apiName getParkingResult
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} cameraId
     *
     * @apiSuccess {Object} result null when there is no result yet, otherwise
     * { revision, processedAt, frameWidth, frameHeight, freeSpaces: [ { parkingAreaId, spaces: [ [ { x, y } x 4 ] ] } ], frame: base64 JPEG or null },
     * free spaces are in pixels of the processed frame
     * @apiSuccess {Object} status processing status of the latest attempt, null before the first one:
     * { status: ok | cameraUnavailable | detectionFailed | stale | saveFailed | notProcessed, reason, time, revision },
     * notProcessed (reason disabled or notReady) when the configuration is not processed at all
     * @apiError notFound parking backend is not configured
     */

    /**
     * @api {post} /api/cameras/parkingResult/:cameraId process the camera right now
     *
     * @apiVersion 1.0.0
     *
     * @apiName processParking
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} cameraId
     *
     * @apiSuccess {Object} processing outcome of the processing cycle { status, reason }, status: ok, cameraUnavailable,
     * detectionFailed, stale, skipped, discarded, noRevision or failed; null when the occupancy service doesn't process the camera
     * (its configuration is disabled or not ready); the result and the status are then read with GET
     * @apiError notFound parking backend is not configured
     * @apiError parkingServiceUnavailable the occupancy service is not configured ("service" of the parking backend) or doesn't answer
     */

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * parkingResult method
         */

        class parkingResult extends api {

            public static function GET($params) {
                $parking = loadBackend("parking");

                if (!$parking) {
                    return api::ERROR("notFound");
                }

                $result = $parking->getResult($params["_id"], true);
                $status = $result !== false ? $parking->getStatus($params["_id"]) : false;

                if ($result === false || $status === false) {
                    return api::ERROR();
                }

                return api::SUCCESS("__asis__", [ "result" => $result, "status" => $status ]);
            }

            public static function POST($params) {
                $parking = loadBackend("parking");

                if (!$parking) {
                    return api::ERROR("notFound");
                }

                $outcome = $parking->processNow($params["_id"]);

                if ($outcome === false) {
                    return api::ERROR($parking->lastFailure() == "parkingServiceUnavailable" ? "parkingServiceUnavailable" : "");
                }

                return api::SUCCESS("__asis__", [ "processing" => $outcome ]);
            }

            public static function index() {
                if (!loadBackend("parking")) {
                    return false;
                }

                return [
                    "GET" => "#same(addresses,house,GET)",
                    "POST" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

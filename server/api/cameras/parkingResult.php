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

            public static function index() {
                if (!loadBackend("parking")) {
                    return false;
                }

                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

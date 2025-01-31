<?php

    /**
     * @api {post} /api/houses/entrance create or add entrance
     *
     * @apiVersion 1.0.0
     *
     * @apiName createEntrance
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} entranceId
     * @apiBody {Number} houseId
     * @apiBody {Number} prefix
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/houses/entrance create or add entrance
     *
     * @apiVersion 1.0.0
     *
     * @apiName addEntrance
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} houseId
     * @apiBody {Number} prefix
     * @apiBody {String="entrance,wicket,gate,barrier"} entranceType
     * @apiBody {String} entrance
     * @apiBody {Number} lat
     * @apiBody {Number} lon
     * @apiBody {Boolean} shared
     * @apiBody {Boolean} plog
     * @apiBody {Number} prefix
     * @apiBody {String} callerId
     * @apiBody {Number} domophoneId
     * @apiBody {Number} domophoneOutput
     * @apiBody {String} cms
     * @apiBody {Number} cmsType
     * @apiBody {Number} cameraId
     * @apiBody {Number[]} altCamerasIds
     * @apiBody {String} cmsLevels
     * @apiBody {Number} path
     *
     * @apiSuccess {Number} entranceId
     */

    /**
     * @api {put} /api/houses/entrance/:entranceId modify entrance
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyEntrance
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} entranceId
     * @apiBody {Number} houseId
     * @apiBody {String="entrance,wicket,gate,barrier"} entranceType
     * @apiBody {String} entrance
     * @apiBody {Number} lat
     * @apiBody {Number} lon
     * @apiBody {Boolean} shared
     * @apiBody {Boolean} plog
     * @apiBody {Number} prefix
     * @apiBody {String} callerId
     * @apiBody {Number} domophoneId
     * @apiBody {Number} domophoneOutput
     * @apiBody {String} cms
     * @apiBody {Number} cmsType
     * @apiBody {Number} cameraId
     * @apiBody {Number[]} altCamerasIds
     * @apiBody {String} cmsLevels
     * @apiBody {Number} path
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/houses/entrance/:entranceId modify entrance
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyEntrance
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} entranceId
     * @apiBody {Number} [houseId]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * entrance method
         */

        class entrance extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                if (@$params["entranceId"]) {
                    $success = $households->addEntrance($params["houseId"], $params["entranceId"], $params["prefix"]);

                    return api::ANSWER($success);
                } else {
                    $entranceId = $households->createEntrance($params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], @$params["altCamerasIds"], $params["cmsLevels"], $params["path"]);

                    return api::ANSWER($entranceId, ($entranceId !== false) ? "entranceId" : false);
                }
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyEntrance($params["_id"], $params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], @$params["altCamerasIds"], $params["cmsLevels"], $params["path"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                if (@$params["houseId"]) {
                    $success = $households->deleteEntrance($params["_id"], $params["houseId"]);
                } else {
                    $success = $households->destroyEntrance($params["_id"]);
                }

                return api::ANSWER($success);
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

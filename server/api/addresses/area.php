<?php

    /**
     * @api {put} /api/addresses/area:areaId update area
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateArea
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} [areaId] regionId
     * @apiBody {Number} regionId
     * @apiBody {String} areaUuid
     * @apiBody {String} areaWithType
     * @apiBody {String} areaType
     * @apiBody {String} areaTypeFull
     * @apiBody {String} area
     * @apiBody {String} timezone
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/area create area
     *
     * @apiVersion 1.0.0
     *
     * @apiName createArea
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} regionId
     * @apiBody {String} areaUuid
     * @apiBody {String} areaWithType
     * @apiBody {String} areaType
     * @apiBody {String} areaTypeFull
     * @apiBody {String} area
     * @apiBody {String} timezone
     *
     * @apiSuccess {Number} areaId
     */

    /**
     * @api {delete} /api/addresses/area:areaId delete area
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteArea
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} [areaId] areaId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * area method
         */

        class area extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyArea($params["_id"], $params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $areaId = $addresses->addArea($params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

                return api::ANSWER($areaId, ($areaId !== false) ? "areaId" : "notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteArea($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
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

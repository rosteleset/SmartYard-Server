<?php

    /**
     * @api {put} /api/addresses/city/:cityId update city
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateCity
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} cityId cityId
     * @apiBody {Number} regionId
     * @apiBody {Number} areaId
     * @apiBody {String} cityUuid
     * @apiBody {String} cityWithType
     * @apiBody {String} cityType
     * @apiBody {String} cityTypeFull
     * @apiBody {String} city
     * @apiBody {String} timezone
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/city create city
     *
     * @apiVersion 1.0.0
     *
     * @apiName createCity
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} regionId
     * @apiBody {Number} areaId
     * @apiBody {String} cityUuid
     * @apiBody {String} cityWithType
     * @apiBody {String} cityType
     * @apiBody {String} cityTypeFull
     * @apiBody {String} city
     * @apiBody {String} timezone
     *
     * @apiSuccess {Number} cityId
     */

    /**
     * @api {delete} /api/addresses/city/:cityId delete city
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCity
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} cityId cityId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * city method
         */

        class city extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyCity($params["_id"], $params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $cityId = $addresses->addCity($params["regionId"], $params["areaId"], $params["cityUuid"], $params["cityWithType"], $params["cityType"], $params["cityTypeFull"], $params["city"], $params["timezone"]);

                return api::ANSWER($cityId, ($cityId !== false) ? "cityId" : "notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteCity($params["_id"]);

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

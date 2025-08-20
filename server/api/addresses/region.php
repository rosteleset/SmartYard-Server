<?php

    /**
     * @api {put} /api/addresses/region/:regionId update region
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateRegion
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} regionId regionId
     * @apiBody {String} regionUuid
     * @apiBody {String} regionIsoCode
     * @apiBody {String} regionWithType
     * @apiBody {String} regionType
     * @apiBody {String} regionTypeFull
     * @apiBody {String} region
     * @apiBody {String} timezone
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/region create region
     *
     * @apiVersion 1.0.0
     *
     * @apiName createRegion
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} regionUuid
     * @apiBody {String} regionIsoCode
     * @apiBody {String} regionWithType
     * @apiBody {String} regionType
     * @apiBody {String} regionTypeFull
     * @apiBody {String} region
     * @apiBody {String} timezone
     *
     * @apiSuccess {Number} regionId
     */

    /**
     * @api {delete} /api/addresses/region/:regionId delete region
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteRegion
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} regionId regionId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * region method
         */

        class region extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyRegion($params["_id"], $params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $regionId = $addresses->addRegion($params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

                return api::ANSWER($regionId, ($regionId !== false)?"regionId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteRegion($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
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

<?php

    /**
     * @api {put} /api/addresses/settlement/:settlementId update settlement
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateSettlement
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} settlementId settlementId
     * @apiBody {Number} areaId
     * @apiBody {Number} cityId
     * @apiBody {String} settlementUuid
     * @apiBody {String} settlementWithType
     * @apiBody {String} settlementType
     * @apiBody {String} settlementTypeFull
     * @apiBody {String} settlement
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/settlement create settlement
     *
     * @apiVersion 1.0.0
     *
     * @apiName createSettlement
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} areaId
     * @apiBody {Number} cityId
     * @apiBody {String} settlementUuid
     * @apiBody {String} settlementWithType
     * @apiBody {String} settlementType
     * @apiBody {String} settlementTypeFull
     * @apiBody {String} settlement
     *
     * @apiSuccess {Number} settlementId
     */

    /**
     * @api {delete} /api/addresses/settlement/:settlementId delete settlement
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteSettlement
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} settlementId settlementId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * settlement method
         */

        class settlement extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifySettlement($params["_id"], $params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $settlementId = $addresses->addSettlement($params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

                return api::ANSWER($settlementId, ($settlementId !== false)?"settlementId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteSettlement($params["_id"]);

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

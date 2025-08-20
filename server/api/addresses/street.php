<?php

    /**
     * @api {put} /api/addresses/street/:streetId update street
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateStreet
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} streetId streetId
     * @apiBody {Number} cityId
     * @apiBody {Number} settlementId
     * @apiBody {String} streetUuid
     * @apiBody {String} streetWithType
     * @apiBody {String} streetType
     * @apiBody {String} streetTypeFull
     * @apiBody {String} street
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/street create street
     *
     * @apiVersion 1.0.0
     *
     * @apiName createStreet
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Number} streetId
     */

    /**
     * @api {delete} /api/addresses/street/:streetId delete street
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteStreet
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} streetId streettId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * street method
         */

        class street extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->modifyStreet($params["_id"], $params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $streetId = $addresses->addStreet($params["cityId"], $params["settlementId"], $params["streetUuid"], $params["streetWithType"], $params["streetType"], $params["streetTypeFull"], $params["street"]);

                return api::ANSWER($streetId, ($streetId !== false)?"streetId":"notAcceptable");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteStreet($params["_id"]);

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

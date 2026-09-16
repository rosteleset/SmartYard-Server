<?php

    /**
     * @api {get} /api/addresses/house/:houseId get house
     *
     * @apiVersion 1.0.0
     *
     * @apiName getHouse
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} houseId houseId
     *
     * @apiSuccess {Object} house
     * @apiSuccess {Number[]} house.companyIds Servicing organizations (empty array when unassigned).
     * @apiSuccess {Number} house.companyId Deprecated: lowest company ID, or 0.
     */

    /**
     * @api {put} /api/addresses/house/:houseId update house
     *
     * @apiVersion 1.0.0
     *
     * @apiName updateHouse
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} houseId houseId
     * @apiBody {Number} settlementId
     * @apiBody {Number} streetId
     * @apiBody {String} houseUuid
     * @apiBody {String} houseType
     * @apiBody {String} houseTypeFull
     * @apiBody {String} houseFull
     * @apiBody {String} house
     * @apiBody {Number[]} [companyIds] Complete set of servicing organizations; [] clears all, omission preserves links.
     * @apiBody {Number} [companyId] Deprecated scalar fallback; ignored when companyIds is supplied.
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/addresses/house create house
     *
     * @apiVersion 1.0.0
     *
     * @apiName createHouse
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} settlementId
     * @apiBody {Number} streetId
     * @apiBody {String} houseUuid
     * @apiBody {String} houseType
     * @apiBody {String} houseTypeFull
     * @apiBody {String} houseFull
     * @apiBody {String} house
     * @apiBody {Number[]} [companyIds] Servicing organizations; defaults to [].
     * @apiBody {Number} [companyId] Deprecated scalar fallback.
     *
     * @apiSuccess {Number} houseId
     */

    /**
     * @api {delete} /api/addresses/house/:houseId delete house
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteHouse
     * @apiGroup addresses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} houseId houseId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * house method
         */

        class house extends api {

            public static function GET($params) {
                $addresses = loadBackend("addresses");

                $house = $addresses->getHouse($params["_id"]);

                return api::ANSWER($house, ($house !== false) ? "house" : "notAcceptable");
            }

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                if (array_key_exists("companyIds", $params) && !is_array($params["companyIds"])) {
                    return api::ANSWER(false, "notAcceptable");
                }
                $companyIds = $params["companyIds"] ?? $params["companyId"] ?? null;
                $success = $addresses->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"], $companyIds);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                if (@$params["magic"]) {
                    $houseId = $addresses->addHouseByMagic($params["magic"]);
                } else {
                    if (array_key_exists("companyIds", $params) && !is_array($params["companyIds"])) {
                        return api::ANSWER(false, "notAcceptable");
                    }
                    $companyIds = $params["companyIds"] ?? $params["companyId"] ?? [];
                    $houseId = $addresses->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"], $companyIds);
                }

                return api::ANSWER($houseId, ($houseId !== false) ? "houseId" : false);
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $success = $addresses->deleteHouse($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                $addresses = loadBackend("addresses");

                if ($addresses) {
                    return [
                        "GET",
                        "PUT",
                        "POST",
                        "DELETE",
                    ];
                } else {
                    return [];
                }
            }
        }
    }

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
     * @apiBody {Number} companyId
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
     * @apiBody {Number} companyId
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

                $success = $addresses->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"], $params["companyId"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                if (@$params["magic"]) {
                    $houseId = $addresses->addHouseByMagic($params["magic"]);
                } else {
                    $houseId = $addresses->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"], $params["companyId"]);
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

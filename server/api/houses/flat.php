<?php

    /**
     * @api {post} /api/houses/flat add flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFlat
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} houseId
     * @apiBody {Number} floor
     * @apiBody {String} flat
     * @apiBody {String} code
     * @apiBody {Object[]} entrances
     * @apiBody {Object[]} apartmentsAndLevels
     * @apiBody {Boolean} manualBlock
     * @apiBody {Boolean} adminBlock
     * @apiBody {String} openCode
     * @apiBody {Boolean} plog
     * @apiBody {Number} autoOpen
     * @apiBody {Number} whiteRabbit
     * @apiBody {Boolean} sipEnabled
     * @apiBody {String} sipPassword
     *
     * @apiSuccess {Number} flatId
     */

    /**
     * @api {put} /api/houses/flat/:flatId modify flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyFlat
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} flatId
     * @apiBody {Number} [floor]
     * @apiBody {String} [flat]
     * @apiBody {String} [code]
     * @apiBody {Object[]} [entrances]
     * @apiBody {Object[]} [apartmentsAndLevels]
     * @apiBody {Boolean} [manualBlock]
     * @apiBody {Boolean} [adminBlock]
     * @apiBody {String} [openCode]
     * @apiBody {Boolean} [plog]
     * @apiBody {Number} [autoOpen]
     * @apiBody {Number} [whiteRabbit]
     * @apiBody {Boolean} [sipEnabled]
     * @apiBody {String} [sipPassword]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/houses/flat/:flatId delete flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFlat
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} flatId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * flat method
         */

        class flat extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $flatId = $households->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["manualBlock"], $params["adminBlock"], $params["openCode"], $params["plog"], $params["autoOpen"], $params["whiteRabbit"], $params["sipEnabled"], $params["sipPassword"]);

                return api::ANSWER($flatId, ($flatId !== false) ? "flatId" : "notAcceptable");
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyFlat($params["_id"], $params);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteFlat($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
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

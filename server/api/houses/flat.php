<?php

    /**
     * @api {get} /api/houses/flat:flatId get flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFlat
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} flat
     */

/**
     * @api {post} /api/houses/flat add flat
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFlat
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
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
     * @apiBody {String} sipAlt
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
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} flatId
     * @apiBody {String} [flat]
     * @apiBody {Number} [floor]
     * @apiBody {String} [code]
     * @apiBody {Object[]} [entrances] if you want to change entrances or apartmentsAndLevels both must be specified
     * @apiBody {Object[]} [apartmentsAndLevels] if you want to change entrances or apartmentsAndLevels both must be specified
     * @apiBody {Boolean} [manualBlock]
     * @apiBody {Boolean} [adminBlock]
     * @apiBody {Boolean} [autoBlock]
     * @apiBody {String} [openCode]
     * @apiBody {Boolean} [plog]
     * @apiBody {Number} [autoOpen]
     * @apiBody {Number} [whiteRabbit]
     * @apiBody {Boolean} [sipEnabled]
     * @apiBody {String} [sipPassword]
     * @apiBody {String} [contract]
     * @apiBody {String} [login]
     * @apiBody {String} [password]
     * @apiBody {String} [cars]
     * @apiBody {Number} [subscribersLimit]
     * @apiBody {String} sipAlt
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
     * @apiHeader {String} Authorization authentication token
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

            public static function GET($params) {
                $households = loadBackend("households");

                $flat = $households->getFlat($params["_id"]);

                return api::ANSWER($flat, ($flat !== false) ? "flat" : "notAcceptable");
            }

            public static function POST($params) {
                $households = loadBackend("households");

                $flatId = $households->addFlat($params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["manualBlock"], $params["adminBlock"], $params["openCode"], $params["plog"], $params["autoOpen"], $params["whiteRabbit"], $params["sipEnabled"], $params["sipPassword"], $params["sipAlt"]);

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

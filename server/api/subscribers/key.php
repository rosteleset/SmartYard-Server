<?php

    /**
     * @api {post} /api/subscribers/key add rfId
     *
     * @apiVersion 1.0.0
     *
     * @apiName addKey
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} rfId
     * @apiBody {Number="0,1,2,3,4,5"} accessType 0 - universal, 1 - subscriber, 2 - flat, 3 - entrance, 4 - house, 5 - company
     * @apiBody {Number} accessTo
     * @apiBody {String} comments
     * @apiBody {Number} watch
     *
     * @apiSuccess {Number} key
     */

    /**
     * @api {put} /api/subscribers/key/:keyId modify rfId
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyKey
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} keyId
     * @apiBody {String} comments
     * @apiBody {Number} watch
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/subscribers/key/:keyId delete rfId
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteKey
     * @apiGroup subscribers
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} keyId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * subscribers api
     */

    namespace api\subscribers {

        use api\api;

        /**
         * key method
         */

        class key extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], @$params["comments"], @$params["watch"] ?: 0);

                return api::ANSWER($keyId, ($keyId !== false) ? "key" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyKey($params["_id"], @$params["comments"], @$params["watch"] ?: 0);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteKey($params["_id"]);

                return api::ANSWER($success);
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

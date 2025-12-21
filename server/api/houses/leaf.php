<?php

    /**
     * @api {post} /api/houses/leaf create tree leaf
     *
     * @apiVersion 1.0.0
     *
     * @apiName addLeaf
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} parent
     * @apiBody {String} name
     *
     * @apiSuccess {String} tree
     */

    /**
     * @api {put} /api/houses/leaf/:tree modify leaf
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyLeaf
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} tree
     * @apiBody {String} name
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/houses/leaf/:tree delete leafs tree
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteTree
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} tree
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * leaf method
         */

        class leaf extends api {

            public static function POST($params) {
                $households = loadBackend("households");

                $tree = $households->addLeaf($params["parent"], $params["name"]);

                return api::ANSWER($tree, ($tree !== false) ? "tree" : false);
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                $success = $households->modifyLeaf($params["_id"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->deleteTree($params["_id"]);

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

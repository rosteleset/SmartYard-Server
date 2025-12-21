<?php

    /**
     * @api {post} /api/cameras/leaf create tree leaf
     *
     * @apiVersion 1.0.0
     *
     * @apiName addLeaf
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} [parent]
     * @apiBody {String} name
     *
     * @apiSuccess {String} tree
     */

    /**
     * @api {put} /api/cameras/leaf/:tree modify leaf
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyLeaf
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} tree
     * @apiBody {String} name
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/cameras/leaf/:tree delete leafs tree
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteTree
     * @apiGroup cameras
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} tree
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * cameras api
     */

    namespace api\cameras {

        use api\api;

        /**
         * leaf method
         */

        class leaf extends api {

            public static function POST($params) {
                $cameras = loadBackend("cameras");

                $tree = $cameras->addLeaf(@$params["parent"], $params["name"]);

                return api::ANSWER($tree, ($tree !== false) ? "tree" : false);
            }

            public static function PUT($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->modifyLeaf($params["_id"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $cameras = loadBackend("cameras");

                $success = $cameras->deleteTree($params["_id"]);

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

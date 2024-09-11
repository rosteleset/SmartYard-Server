<?php

    /**
     * @api {get} /api/houses/house/:treeOrFrom get tree
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPathPart
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} treeOrFrom tree or parent
     * @apiQuery {String} [search]
     * @apiQuery {String} [from]
     * @apiQuery {String} [tree]
     * @apiQuery {Boolean} [withParents]
     *
     * @apiSuccess {Object[]} tree
     */

    /**
     * @api {post} /api/houses/house/:parentId add node
     *
     * @apiVersion 1.0.0
     *
     * @apiName addTreeNode
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} parentId
     * @apiBody {String} text
     * @apiBody {String} icon
     *
     * @apiSuccess {Number} nodeId
     */

    /**
     * @api {put} /api/houses/house/:nodeId modify node
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyTreeNode
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} nodeId
     * @apiBody {String} text
     * @apiBody {String} icon
     *
     * @apiSuccess {Boolean} oprationResult
     */

    /**
     * @api {delete} /api/houses/house/:nodeId delete node
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteTreeNode
     * @apiGroup houses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} nodeId
     *
     * @apiSuccess {Boolean} oprationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * path method
         */

        class path extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                if (!$households) {
                    return api::ERROR();
                } else {
                    $tree = [];

                    if (@$params["search"]) {
                        $tree = $households->searchPath($params["_id"], $params["search"]);
                    } else {
                        $tree = $households->getPath($params["_id"], !!@$params["withParents"], false, !!@$params["withParents"] ? $params["_id"] : false, @$params["tree"]);
                    }

                    return api::ANSWER($tree, "tree");
                }
            }

            public static function POST($params) {
                $households = loadBackend("households");

                if (!$households) {
                    return api::ERROR();
                } else {
                    if ((int)$params["_id"]) {
                        return api::ANSWER($households->addPathNode($params["_id"], @$params["text"], @$params["icon"]), "nodeId");
                    } else {
                        return api::ANSWER($households->addRootPathNode($params["_id"], @$params["text"], @$params["icon"]), "nodeId");
                    }
                }
            }

            public static function PUT($params) {
                $households = loadBackend("households");

                if (!$households) {
                    return api::ERROR();
                } else {
                    return api::ANSWER($households->modifyPathNode($params["_id"], @$params["text"], @$params["icon"]));
                }
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                if (!$households) {
                    return api::ERROR();
                } else {
                    return api::ANSWER($households->deletePathNode($params["_id"]));
                }
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                    "POST" => "#same(addresses,house,POST)",
                    "PUT" => "#same(addresses,house,PUT)",
                    "DELETE" => "#same(addresses,house,DELETE)",
                ];
            }
        }
    }

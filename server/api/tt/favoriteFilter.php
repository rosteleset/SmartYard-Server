<?php

    /**
     * @api {post} /api/tt/favoriteFilter/:customFilterId add filter to favorites
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFavoriteFilter
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} customFilterId
     * @apiBody {String} project
     * @apiBody {Boolean} rightSide
     * @apiBody {String} icon
     * @apiBody {String} color
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/favoriteFilter/:customFilterId delete filter from favorites
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFavoriteFilter
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {String} customFilterId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * favoriteFilter method
         */

        class favoriteFilter extends api {

            public static function POST($params) {
                $success = loadBackend("tt")->addFavoriteFilter($params["_id"], @$params["project"], @$params["rightSide"], @$params["icon"], @$params["color"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFavoriteFilter($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,tt,GET)",
                        "DELETE" => "#same(tt,tt,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

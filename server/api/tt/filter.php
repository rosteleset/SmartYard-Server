<?php

    /**
     * @api {get} /api/tt/filter/:filterId get filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFilter
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} filterId
     *
     * @apiSuccess {String} body
     */

    /**
     * @api {get} /api/tt/filter get filters
     *
     * @apiVersion 1.0.0
     *
     * @apiName addFavoriteFilter
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object[]} filters
     */

    /**
     * @api {put} /api/tt/filter/:filterId modify filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName putFilter
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} filterId
     * @apiBody {String} body
     *
     * @apiSuccess {String} body
     */

    /**
     * @api {delete} /api/tt/filter/:filterId delete filter
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFilter
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} filterId
     *
     * @apiSuccess {String} body
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * filter method
         */

        class filter extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if ($tt) {
                    if (@$params["_id"]) {
                        if (@$params["_id"]) {
                            return api::ANSWER($tt->getFilter($params["_id"]), "body");
                        }
                    } else {
                        return api::ANSWER($tt->getFilters(), "filters");
                    }
                }

                return api::ERROR();
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->putFilter($params["_id"], $params["body"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteFilter($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,tt,GET)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

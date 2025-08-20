<?php

    /**
     * @api {get} /api/houses/watch/:flatId get watchers
     *
     * @apiVersion 1.0.0
     *
     * @apiName getWatchers
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} flatId
     *
     * @apiSuccess {Array} watchers
     */

    /**
     * @api {delete} /api/houses/flat/:houseWatcherId unwatch
     *
     * @apiVersion 1.0.0
     *
     * @apiName unwatch
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} houseWatcherId
     *
     *  @apiSuccess {Boolean} operationResult
     */

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * watch method
         */

        class watch extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $watchers = $households->watchers(null, $params["_id"]);

                return api::ANSWER($watchers, ($watchers !== false) ? "watchers" : "notAcceptable");
            }

            public static function DELETE($params) {
                $households = loadBackend("households");

                $success = $households->unwatch($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                    "DELETE" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }

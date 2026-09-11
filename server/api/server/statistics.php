<?php

    /**
     * @api {get} /api/server/statistics statistics
     *
     * @apiVersion 1.0.0
     *
     * @apiName statistics
     * @apiGroup server
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} statistics
     */

    /**
     * server api
     */

    namespace api\server {

        use api\api;

        /**
         * statistics method
         */

        class statistics extends api {

            public static function GET($params) {
                $statistics = loadBackend("statistics");

                if ($statistics) {
                    return api::ANSWER($statistics->statistics(), "statistics");
                } else {
                    return api::ANSWER(false);
                }
            }

            public static function index() {
                if (loadBackend("statistics")) {
                    return [
                        "GET",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

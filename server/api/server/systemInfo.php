<?php

    /**
     * @api {post} /api/server/systemInfo system info
     *
     * @apiVersion 1.0.0
     *
     * @apiName version
     * @apiGroup server
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} system info
     */

    /**
     * server api
     */

    namespace api\server {

        use api\api;

        /**
         * systemInfo method
         */

        class systemInfo extends api {

            public static function GET($params) {
                $systemInfo = loadBackend("systemInfo");

                if ($systemInfo) {
                    return api::ANSWER($systemInfo->systemInfo(), "systemInfo");
                } else {
                    api::ANSWER(false);
                }
            }

            public static function index() {
                if (loadBackend("systemInfo")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

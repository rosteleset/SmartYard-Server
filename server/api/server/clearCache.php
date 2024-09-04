<?php

    /**
     * @api {post} /server/clearCache clear cache
     *
     * @apiVersion 1.0.0
     *
     * @apiName version
     * @apiGroup server
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * server api
     */

    namespace api\server {

        use api\api;

        /**
         * clearCache method
         */

        class clearCache extends api {

            public static function POST($params) {
                clearCache($params["_uid"]);

                return api::ANSWER();
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

<?php

    /**
     * @api {post} /server/clearCache get version
     *
     * @apiVersion 1.0.0
     *
     * @apiName version
     * @apiGroup server
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/server/clearCache
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
                clear_cache($params["_uid"]);

                return api::ANSWER();
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

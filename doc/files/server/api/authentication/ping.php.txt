<?php

    /**
     * @api {post} /authentication/ping check token
     *
     * @apiVersion 1.0.0
     *
     * @apiName ping
     * @apiGroup authentication
     *
     * @apiHeader {string} token authentication token
     *
     * @apiParam {string} [ua] user agent
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError tokenNotFound token not found
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "tokenNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/authentication/ping \
     *      -H 'Authorization: Bearer aa8d362f-ffeb-4c2b-9e0f-f70ecb0078c0'
     */

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * ping method
         */

        class ping extends api {

            public static function POST($params) {
                return [
                    "204" => null,
                ];
            }

            public static function index() {
                return [ "POST" ];
            }
        }
    }

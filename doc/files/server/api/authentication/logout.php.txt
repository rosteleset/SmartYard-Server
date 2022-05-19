<?php

    /**
     * @api {post} /authentication/logout logout
     *
     * @apiVersion 1.0.0
     *
     * @apiName logout
     * @apiGroup authentication
     *
     * @apiHeader {string} token authentication token
     *
     * @apiParam {string=all,this} [mode=this] logout all
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError tokenNotFound token not found
     * @apiError forbidden access denied
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "tokenNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/authentication/logout \
     *      -H 'Authorization: Bearer aa8d362f-ffeb-4c2b-9e0f-f70ecb0078c0'
     *      -H 'Content-Type: application/json' \
     *      -d '{"mode":"all"}'
     */

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * logout method
         */

        class logout extends api {

            public static function POST($params) {
                $params["_backends"]["authentication"]->logout($params["_token"], @$params['mode'] == 'all');

                return [
                    "204" => null,
                ];
            }

            public static function index() {
                return [ "POST" ];
            }
        }
    }


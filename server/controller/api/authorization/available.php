<?php

/**
     * @api {get} /authorization/available available methods
     *
     * @apiVersion 1.0.0
     *
     * @apiName available
     * @apiGroup authorization
     *
     * @apiHeader {string} token authentication token
     *
     * @apiSuccess {array} available list of available methods
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "available": {
     *          "accounts": {
     *              "users": [
     *                  "GET"
     *              ],
     *              "user": [
     *                  "GET", "PUT", "POST", "DELETE"
     *              ]
     *          }
     *      }
     *  }
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
     *  curl http://127.0.0.1:8000/server/api.php/authentication/available \
     *      -H 'Authorization: Bearer aa8d362f-ffeb-4c2b-9e0f-f70ecb0078c0'
     */

    /**
     * authorization api
     */

    namespace api\authorization {

        use api\api;

        /**
         * available method
         */

        class available extends api {

            public static function GET($params) {
                return api::SUCCESS("available", $params["_backends"]["authorization"]->allowedMethods($params["_uid"]));
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

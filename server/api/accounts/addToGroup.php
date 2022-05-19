<?php

    /**
     * @api {post} /accounts/addToGroup/:uid add user to group(s)
     *
     * @apiVersion 1.0.0
     *
     * @apiName addToGroup
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} uid user id
     * @apiParam {Number[]} gids group ids
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError userNotFound user not found
     * @apiError groupNotFound group(s) not found
     * @apiError forbidden access denied
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "userNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/addToGroup/1 \
     *      -H 'Content-Type: application/json' \
     *      -d '{"gids":[1,2,3]}'
     */

    /**
     * accounts api
     */

    namespace api\accounts {

        use api\api;

        /**
         * addToGroup method
         */

        class addToGroup extends api {

            public static function POST($params) {

            }

            public static function index() {
                $groups = loadBackend("groups");

                if ($groups && $groups->capabilities()["mode"] === "rw") {
                    return [ "POST" ];
                } else {
                    return [];
                }
            }
        }
    }

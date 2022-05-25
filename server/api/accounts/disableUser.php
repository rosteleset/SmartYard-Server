<?php

    /**
     * @api {post} /accounts/disableUser/:uid disable user
     *
     * @apiVersion 1.0.0
     *
     * @apiName disableUser
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} uid uid
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiError invalidUid invalid uid
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 406 Not Acceptable
     *  {
     *      "error": "invalidUid"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/accounts/disableUser/1
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * disableUser method
         */

        class disableUser extends api {

            public static function POST($params) {
                $success = $params["_backends"]["users"]->enableUser($params["_id"], false);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                $users = loadBackend("users");

                if ($users && $users->capabilities()["mode"] === "rw") {
                    return [ "POST" ];
                } else {
                    return [];
                }
            }
        }
    }


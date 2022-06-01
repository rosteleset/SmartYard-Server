<?php

    /**
     * @api {post} /accounts/password/:uid set user password
     *
     * @apiVersion 1.0.0
     *
     * @apiName password
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} uid uid
     * @apiParam {String} password new password
     *
     * @apiError forbidden access denied
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "userNotFound"
     *  }
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 204 OK
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/accounts/password/1
     *      -H 'Content-Type: application/json' \
     *      -d '{"password":"my_new_password"}'
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * users method
         */

        class password extends api {

            public static function POST($params) {
                $success = $params["_backends"]["users"]->setPassword(@$params["_id"], $params["password"]);

                return self::ANSWER($success, ($success !== false)?false:"notFound");
            }

            public static function index() {
                $users = loadBackend("users");

                if ($users && $users->capabilities()["mode"] === "rw") {
                    return [
                        "POST" => "#personal",
                    ];
                } else {
                    return false;
                }
            }
        }
    }


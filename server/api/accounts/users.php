<?php

    /**
     * @api {get} /accounts/users get users
     *
     * @apiVersion 1.0.0
     *
     * @apiName getUsers
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiError forbidden access denied
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "userNotFound"
     *  }
     *
     * @apiSuccess {Object[]} users array of users
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "users": [
     *          {
     *              "uid": 1,
     *              "login": "my_loggin",
     *              "realName": "my_real_name"
     *          }
     *      ]
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl http://127.0.0.1:8000/server/api.php/accounts/users
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * users method
         */

        class users extends api {

            public static function GET($params) {
                $users = $params["_backends"]["users"]->getUsers(@$params["withSessions"]);

                return api::ANSWER($users, ($users !== false)?"users":"notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }


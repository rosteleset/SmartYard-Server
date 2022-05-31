<?php

    /**
     * @api {post} /authentication/login login
     *
     * @apiVersion 1.0.0
     *
     * @apiName login
     * @apiGroup authentication
     *
     * @apiParam {string} login login
     * @apiParam {string} password password
     * @apiParam {boolean} [rememberMe] generate persistent (10 year ttl) token
     * @apiParam {string} [ua] user agent
     * @apiParam {string} [did] device id
     *
     * @apiSuccess {string} token authentication token
     *
     * @apiSuccessExample Success-Response:
     *  HTTP/1.1 200 OK
     *  {
     *      "token": "aa8d362f-ffeb-4c2b-9e0f-f70ecb0078c0"
     *  }
     *
     * @apiError userNotFound The login and password of the user was not found
     *
     * @apiErrorExample Error-Response:
     *  HTTP/1.1 404 Not Found
     *  {
     *      "error": "userNotFound"
     *  }
     *
     * @apiExample {curl} Example usage:
     *  curl -X POST http://127.0.0.1:8000/server/api.php/authentication/login \
     *      -H 'Content-Type: application/json' \
     *      -d '{"login":"my_login","password":"my_password"}'
     */

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * login method
         */

        class login extends api {

            public static function POST($params) {

                $auth = $params["_backends"]["authentication"]->login($params["login"], $params["password"], $params["rememberMe"] && $params["ua"] && $params["did"], trim($params["ua"]), trim($params["did"]));

                if ($auth["result"]) {
                    return [
                        "200" => [
                            "token" => $auth["token"],
                        ],
                    ];
                } else {
                    return [
                        $auth["code"] => [
                            "error" => $auth["error"],
                        ]
                    ];
                }
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

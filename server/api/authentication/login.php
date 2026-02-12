<?php

    /**
     * @api {post} /api/authentication/login login
     *
     * @apiVersion 1.0.0
     *
     * @apiName login
     * @apiGroup authentication
     *
     * @apiBody {String} login
     * @apiBody {String} password
     * @apiBody {String} [rememberMe]
     * @apiBody {String} [did]
     * @apiBody {String} [oneCode]
     *
     * @apiSuccess {Object} login or otp results
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
                global $redis;

                $pk = $redis->get("PK");

                if ($pk && @$params["encrypted"]) {
                    $params["password"] = decryptData($params["password"], $pk);
                }

                $auth = $params["_backends"]["authentication"]->login($params["login"], $params["password"], @$params["rememberMe"] && $params["_ua"] && @$params["did"], trim($params["_ua"]), trim(@$params["did"]), $params["_ip"], @$params["oneCode"]);

                if ($auth && @$auth["otp"]) {
                    return [
                        "200" => [
                            "otp" => true,
                        ],
                    ];
                }

                if ($auth && $auth["result"]) {
                    return [
                        "200" => [
                            "token" => $auth["token"],
                        ],
                    ];
                }

                return [
                    $auth["code"] => [
                        "error" => $auth["error"],
                    ]
                ];
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

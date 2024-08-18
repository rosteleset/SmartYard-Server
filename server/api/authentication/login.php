<?php

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

                $auth = $params["_backends"]["authentication"]->login($params["login"], $params["password"], $params["rememberMe"] && $params["ua"] && $params["did"], trim($params["ua"]), trim($params["did"]), $params["_ip"], @$params["oneCode"]);

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

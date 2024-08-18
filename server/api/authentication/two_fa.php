<?php

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * two_fa method
         */

        class two_fa extends api {

            public static function POST($params) {

                $two_fa = $params["_backends"]["authentication"]->two_fa($params["_token"], @$params["oneCode"]);

                return api::ANSWER($two_fa, ($two_fa !== false) ? "two_fa" : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

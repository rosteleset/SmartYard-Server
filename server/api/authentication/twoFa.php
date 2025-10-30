<?php

    /**
     * @api {post} /api/authentication/twoFa twoFa request and confirm
     *
     * @apiVersion 1.0.0
     *
     * @apiName twoFa
     * @apiGroup authentication
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} [oneCode]
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * twoFa method
         */

        class twoFa extends api {

            public static function POST($params) {

                $twoFa = $params["_backends"]["authentication"]->twoFa($params["_token"], @$params["oneCode"]);

                return api::ANSWER($twoFa, ($twoFa !== false) ? "twoFa" : "notAcceptable");
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

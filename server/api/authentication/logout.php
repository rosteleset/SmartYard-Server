<?php

    /**
     * @api {post} /api/authentication/logout logout
     *
     * @apiVersion 1.0.0
     *
     * @apiName logout
     * @apiGroup authentication
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String=all,this} [mode=all] logout all
     */

    /**
     * authentication api
     */

    namespace api\authentication {

        use api\api;

        /**
         * logout method
         */

        class logout extends api {

            public static function POST($params) {
                $params["_backends"]["authentication"]->logout($params["_token"], @$params['mode'] == 'all');

                return [
                    "204" => null,
                ];
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

<?php

    /**
     * @api {get} /api/authorization/available available methods
     *
     * @apiVersion 1.0.0
     *
     * @apiName available
     * @apiGroup authorization
     *
     * @apiHeader {String} token authentication token
     *
     * @apiSuccess {Object} available list of available methods
     */

    /**
     * authorization api
     */

    namespace api\authorization {

        use api\api;

        /**
         * available method
         */

        class available extends api {

            public static function GET($params) {
                return api::SUCCESS("available", $params["_backends"]["authorization"]->allowedMethods($params["_uid"]));
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

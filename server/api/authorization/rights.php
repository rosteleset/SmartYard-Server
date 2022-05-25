<?php

    /**
     * @api {get} /authorization/rights get rights of all users and groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName rights
     * @apiGroup authorization
     *
     * @apiHeader {string} token authentication token
     */

    /**
     * @api {put} /authorization/rights set rights of all users and groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName rights
     * @apiGroup authorization
     *
     * @apiHeader {string} token authentication token
     */

    /**
     * authorization api
     */

    namespace api\authorization {

        /**
         * available method
         */

        class rights extends \api\api {

            public static function GET($params) {
                //
            }

            public static function PUT($params) {
                //
            }

            public static function index() {
                $authorization = loadBackend("authorization");

                if ($authorization->capabilities() == "rw") {
                    return [ "GET", "PUT" ];
                } else {
                    return [ ];
                }
            }
        }
    }

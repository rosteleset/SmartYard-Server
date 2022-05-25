<?php

    /**
     * @api {get} /authorization/methods get all methods available on server
     *
     * @apiVersion 1.0.0
     *
     * @apiName methods
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

        class methods extends \api\api {

            public static function GET($params) {
                //
            }

            public static function index() {
                return [ "GET" ];
            }
        }
    }

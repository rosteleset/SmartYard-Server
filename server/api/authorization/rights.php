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

        use api\api;

        /**
         * available method
         */

        class rights extends api {

            public static function GET($params) {
                $rights = $params["_backends"]["authorization"]->getRights();

                return api::ANSWER($rights, ($rights !== false)?"rights":"notFound");
            }

            public static function PUT($params) {
                //
            }

            public static function index() {
                $authorization = loadBackend("authorization");

                if ($authorization->capabilities() == "rw") {
                    return [ "GET", "PUT", "POST", "DELETE" ];
                } else {
                    return [ ];
                }
            }
        }
    }

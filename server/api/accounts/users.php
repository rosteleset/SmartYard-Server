<?php

    /**
     * @api {get} /api/accounts/users get users
     *
     * @apiVersion 1.0.0
     *
     * @apiName getUsers
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * users method
         */

        class users extends api {

            public static function GET($params) {
                $users = $params["_backends"]["users"]->getUsers(@$params["withSessions"]);

                return api::ANSWER($users, ($users !== false)?"users":"notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

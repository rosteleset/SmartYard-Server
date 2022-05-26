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

            public static function POST($params) {
                $success = $params["_backends"]["authorization"]->setRights($params["user"], $params["user"]?$params["uid"]:$params["gid"], $params["action"], $params["allow"]);

                return api::ANSWER($success, ($success !== false)?false:"unknown");
            }

            public static function index() {
                $authorization = loadBackend("authorization");

                if ($authorization->capabilities()["mode"] === "rw") {
                    return [ "GET", "POST", "DELETE" ];
                } else {
                    return [ ];
                }
            }
        }
    }

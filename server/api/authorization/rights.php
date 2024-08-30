<?php

    /**
     * @api {get} /api/authorization/rights get rights of all users and groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName getRights
     * @apiGroup authorization
     *
     * @apiHeader {String} token authentication token
     *
     * @apiSuccess {Object} rights
     */

    /**
     * @api {post} /api/authorization/rights set rights of all users and groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName setRights
     * @apiGroup authorization
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {Boolean} user
     * @apiBody {Number} id uid or gid
     * @apiBody {String} api
     * @apiBody {String} method
     * @apiBody {String[]="GET,POST,PUT,DELETE"} allow
     * @apiBody {String[]="GET,POST,PUT,DELETE"} deny
     *
     * @apiSuccess {Boolean} operationResult
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

                return api::ANSWER($rights, ($rights !== false) ? "rights" : "notFound");
            }

            public static function POST($params) {
                $success = $params["_backends"]["authorization"]->setRights($params["user"], $params["user"] ? $params["uid"] : $params["gid"], $params["api"], $params["method"], $params["allow"], $params["deny"]);

                return api::ANSWER($success, ($success !== false) ? false : "unknown");
            }

            public static function index() {
                $authorization = loadBackend("authorization");

                if ($authorization->capabilities() && $authorization->capabilities()["mode"] === "rw") {
                    return [ "GET", "POST", ];
                } else {
                    return [ ];
                }
            }
        }
    }

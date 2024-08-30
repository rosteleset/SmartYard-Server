<?php

    /**
     * @api {post} /api/accounts/password/:uid set user password
     *
     * @apiVersion 1.0.0
     *
     * @apiName password
     * @apiGroup users
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} uid uid
     * @apiBody {String} password new password
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * users method
         */

        class password extends api {

            public static function POST($params) {
                $success = $params["_backends"]["users"]->setPassword(@$params["_id"], $params["password"]);

                return self::ANSWER($success, ($success !== false)?false:"notFound");
            }

            public static function index() {
                $users = loadBackend("users");

                if ($users && $users->capabilities()["mode"] === "rw") {
                    return [
                        "POST" => "#personal",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

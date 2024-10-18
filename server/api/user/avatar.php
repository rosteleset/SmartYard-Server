<?php

    /**
     * @api {get} /user/avatar get user avatar
     *
     * @apiVersion 1.0.0
     *
     * @apiName getAvatar
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} avatar
     */

    /**
     * @api {put} /user/avatar put user avatar
     *
     * @apiVersion 1.0.0
     *
     * @apiName putAvatar
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Object} avatar
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * avatar method
         */

        class avatar extends api {

            public static function GET($params) {
                $avatar = $params["_backends"]["users"]->getAvatar();

                return api::ANSWER($avatar, ($avatar !== false) ? "avatar" : "notFound");
            }

            public static function PUT($params) {
                $params["_backends"]["users"]->putAvatar(@$params["avatar"]);

                return api::ANSWER();
            }

            public static function index() {
                return [
                    "GET" => "#common",
                    "PUT" => "#common",
                ];
            }
        }
    }

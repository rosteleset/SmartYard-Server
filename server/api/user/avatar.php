<?php

    /**
     * @api {get} /user/avatar/:id get user avatar
     *
     * @apiVersion 1.0.0
     *
     * @apiName getAvatar
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} id
     *
     * @apiSuccess {Object} avatar
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
                $avatar = $params["_backends"]["users"]->getAvatar(@$params["_id"]);

                return api::ANSWER($avatar, ($avatar !== false) ? "avatar" : "notFound");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

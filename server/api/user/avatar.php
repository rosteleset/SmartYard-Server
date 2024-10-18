<?php

    /**
     * @api {get} /user/avatar/:id get user avatar
     *
     * @apiVersion 1.0.0
     *
     * @apiName getAvatar
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} id
     *
     * @apiSuccess {Object} avatar
     */

    /**
     * @api {put} /user/avatar/:id put user avatar
     *
     * @apiVersion 1.0.0
     *
     * @apiName putAvatar
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} id
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
                $avatar = $params["_backends"]["users"]->getAvatar(@$params["_id"]);

                return api::ANSWER($avatar, ($avatar !== false) ? "avatar" : "notFound");
            }

            public static function PUT($params) {
                $params["_backends"]["users"]->putAvatar(@$params["_id"], @$params["avatar"]);

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

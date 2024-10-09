<?php

    /**
     * @api {post} /user/notify/:uid send notification to user
     *
     * @apiVersion 1.0.0
     *
     * @apiName putSettings
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} uid
     * @apiBody {String} subject
     * @apiBody {String} message
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * notify method
         */

        class notify extends api {

            public static function POST($params) {

                error_log(print_r([
                    @$params["_id"], @$params["subject"], @$param["message"],
                ], 1));

                $params["_backends"]["users"]->notify(@$params["_id"], @$params["subject"], @$param["message"]);

                return api::ANSWER();
            }

            public static function index() {
                return [
                    "POST" => "#common",
                ];
            }
        }
    }

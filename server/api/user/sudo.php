<?php

    /**
     * @api {put} /user/sudo enter admin mode
     *
     * @apiVersion 1.0.0
     *
     * @apiName sudoOn
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * @api {delete} /user/sudo exit admin mode
     *
     * @apiVersion 1.0.0
     *
     * @apiName sudoOff
     * @apiGroup user
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * sudo method
         */

        class sudo extends api {

            public static function PUT($params) {
                $success = $params["_backends"]["users"]->sudoOn(@$params["_realUid"], @$params["password"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $success = $params["_backends"]["users"]->sudoOff();

                return api::ANSWER($success);
            }

            public static function index() {
                return [
                    "PUT" => "#common",
                    "DELETE" => "#common",
                ];
            }
        }
    }

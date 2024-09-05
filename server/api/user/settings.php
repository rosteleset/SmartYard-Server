<?php

    /**
     * @api {get} /user/settings get user settings
     *
     * @apiVersion 1.0.0
     *
     * @apiName getSettings
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} settings
     */

    /**
     * @api {put} /user/settings put user settings
     *
     * @apiVersion 1.0.0
     *
     * @apiName putSettings
     * @apiGroup user
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Object} settings
     *
     * @apiSuccess {Boolean} true
     */

    /**
     * user namespace
     */

    namespace api\user {

        use api\api;

        /**
         * settings method
         */

        class settings extends api {

            public static function GET($params) {
                $settings = $params["_backends"]["users"]->getSettings();

                return api::ANSWER($settings, ($settings !== false) ? "settings" : "notFound");
            }

            public static function PUT($params) {
                $params["_backends"]["users"]->putSettings(@$params["settings"]);

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

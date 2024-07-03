<?php

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

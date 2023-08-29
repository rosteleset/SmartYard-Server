<?php

    /**
     * providers api
     */

    namespace api\providers {

        use api\api;

        /**
         * providers method
         */

        class json extends api {

            public static function GET($params) {
                $providers = loadBackend("providers");

                $providers = $providers->getJson();

                return api::ANSWER($providers, ($providers !== false)?"json":"notAcceptable");
            }

            public static function PUT($params) {
                $providers = loadBackend("providers");

                $success = $providers->putJson($params["body"]);

                return api::ANSWER($success);
            }

            public static function index() {
                $providers = loadBackend("providers");

                if ($providers) {
                    return [
                        "GET" => "#same(providers,provider,GET)",
                        "PUT" => "#same(providers,provider,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

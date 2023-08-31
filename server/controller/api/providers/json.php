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
                $providers = backend("providers");

                $providers = $providers->getJson();

                return api::ANSWER($providers, ($providers !== false)?"json":"notAcceptable");
            }

            public static function PUT($params) {
                $providers = backend("providers");

                $success = $providers->putJson($params["body"]);

                return api::ANSWER($success);
            }

            public static function index() {
                $providers = backend("providers");

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

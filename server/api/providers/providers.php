<?php

    /**
     * providers api
     */

    namespace api\providers {

        use api\api;

        /**
         * providers method
         */

        class providers extends api {

            public static function GET($params) {
                $providers = loadBackend("providers");

                $providers = $providers->getProviders();

                return api::ANSWER($providers, ($providers !== false)?"providers":"notAcceptable");
            }

            public static function index() {
                return [
                    "GET",
                ];
            }
        }
    }

<?php

    /**
     * providers api
     */

    namespace api\providers {

        use api\api;

        /**
         * providers method
         */

        class provider extends api {

            public static function GET($params) {
                $providers = loadBackend("providers");

                $providers = $providers->getProviders();

                return api::ANSWER($providers, ($providers !== false)?"providers":"notAcceptable");
            }

            public static function PUT($params) {
                $providers = loadBackend("providers");

                $success = $providers->modifyProvider($params["_id"], @$params["uid"], @$params["name"], @$params["baseUrl"], @$params["logo"], @$params["tokenCommon"], @$params["tokenSms"], @$params["hidden"]);

                return api::ANSWER($success);
            }

            public static function POST($params) {
                $providers = loadBackend("providers");

                $success = $providers->addProvider(@$params["uid"], @$params["name"], @$params["baseUrl"], @$params["logo"], @$params["tokenCommon"], @$params["tokenSms"], @$params["hidden"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $providers = loadBackend("providers");

                $success = $providers->deleteProvider($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                $providers = loadBackend("providers");

                if ($providers) {
                    return [
                        "GET",
                        "PUT",
                        "POST" => "#same(providers,provider,PUT)",
                        "DELETE" => "#same(providers,provider,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

<?php

    /**
     * providers api
     */

    namespace api\providers {

        use api\api;

        /**
         * provider method
         */

        class provider extends api {

            public static function GET($params) {
                $providers = loadBackend("providers");

                $provider = $providers->getProvider($params["_id"]);

                return api::ANSWER($provider, ($provider !== false)?"provider":"notAcceptable");
            }

            public static function PUT($params) {
                $providers = loadBackend("providers");

                $success = $providers->modifyProvider($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function POST($params) {
                $providers = loadBackend("providers");

                $providerId = $providers->addProvider();

                return api::ANSWER($providerId, ($providerId !== false)?"providerId":false);
            }

            public static function DELETE($params) {
                $providers = loadBackend("providers");

                $success = $providers->deleteProvider($params["_id"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                return [
                    "GET",
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            }
        }
    }

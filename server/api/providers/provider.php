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

                return api::ANSWER($providers, ($providers !== false)?"json":"notAcceptable");
            }

            public static function PUT($params) {
                $providers = loadBackend("providers");

                $success = $providers->modifyProvider($params["_id"], $params["id"], $params["name"], $params["baseUrl"], $params["logo"], $params["token"], $params["allow_sms"], $params["allow_flash_call"], $params["allow_outgoing_call"]);

                return api::ANSWER($success);
            }

            public static function POST($params) {
                $providers = loadBackend("providers");

                $success = $providers->createProvider($params["id"], $params["name"], $params["baseUrl"], $params["logo"], $params["token"], $params["allow_sms"], $params["allow_flash_call"], $params["allow_outgoing_call"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $providers = loadBackend("providers");

                $success = $providers->deleteProvider($params["_id"]);

                return api::ANSWER($success);
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

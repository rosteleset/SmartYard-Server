<?php

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * domophones method
         */

        class domophones extends api {

            public static function GET($params) {
                $households = loadBackend("households");
                $configs = loadBackend("configs");
                $sip = loadBackend("sip");

                if (!$households) {
                    return api::ERROR();
                } else {
                    $response = [
                        "domophones" => $households->getDomophones(),
                        "models" => $configs->getDomophonesModels(),
                        "servers" => $sip->server("all"),
                    ];

                    return api::ANSWER($response, "domophones");
                }
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

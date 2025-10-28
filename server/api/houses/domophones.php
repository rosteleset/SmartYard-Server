<?php

    /**
     * @api {get} /api/houses/domophones get all domophones devices
     *
     * @apiVersion 1.0.0
     *
     * @apiName domophones
     * @apiGroup houses
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} domophones
     */

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
                        "domophones" => $households->getDomophones("all", false, true),
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

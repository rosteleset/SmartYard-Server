<?php

    /**
     * @api {get} /api/mqtt/mqtt get mqtt config
     *
     * @apiVersion 1.0.0
     *
     * @apiName config
     * @apiGroup mqtt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object} config
     */

    /**
     * mqtt api
     */

    namespace api\mqtt {

        use api\api;

        /**
         * config method
         */

        class config extends api {

            public static function GET($params) {
                $mqtt = loadBackend("mqtt");

                if ($mqtt) {
                    $config = $mqtt->getConfig();
                }

                return api::ANSWER($config, ($config !== false) ? "config" : false);
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

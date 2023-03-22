<?php

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

                return api::ANSWER($config, ($config !== false)?"config":false);
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

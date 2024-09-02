<?php

    /**
     * houses api
     */

    namespace api\houses {

        use api\api;

        /**
         * flats method
         */

        class flats extends api {

            public static function GET($params) {
                $households = loadBackend("households");

                $flats = $households->getFlats($params["by"], $params);

                return api::ANSWER($flats, ($flats !== false) ? "flats" : "notAcceptable");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }

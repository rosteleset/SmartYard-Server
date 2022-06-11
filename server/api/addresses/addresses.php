<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * addresses method
         */

        class addresses extends api {

            public static function GET($params) {
                $addresses = loadBackend("addresses");

                $r = [
                    "regions" => $addresses->getRegions(),
                    "areas" => $addresses->getAreas(),
                    "cities" => $addresses->getCities(),
                    "settlements" => [],
                    "streets" => [],
                ];

                return api::ANSWER($r, ($r !== false)?"addresses":"404");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

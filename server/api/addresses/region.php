<?php

    /**
     * addresses api
     */

    namespace api\addresses {

        use api\api;

        /**
         * addresses method
         */

        class region extends api {

            public static function PUT($params) {
                $addresses = loadBackend("addresses");

                $r = [
                    "regions" => $addresses->getRegions(),
                    "areas" => [],
                    "cities" => [],
                    "settlements" => [],
                    "streets" => [],
                ];

                return api::ANSWER($r, ($r !== false)?"addresses":"404");
            }

            public static function POST($params) {
                $addresses = loadBackend("addresses");

                $r = [
                    "regions" => $addresses->getRegions(),
                    "areas" => [],
                    "cities" => [],
                    "settlements" => [],
                    "streets" => [],
                ];

                return api::ANSWER($r, ($r !== false)?"addresses":"404");
            }

            public static function DELETE($params) {
                $addresses = loadBackend("addresses");

                $r = [
                    "regions" => $addresses->getRegions(),
                    "areas" => [],
                    "cities" => [],
                    "settlements" => [],
                    "streets" => [],
                ];

                return api::ANSWER($r, ($r !== false)?"addresses":"404");
            }

            public static function index() {
                return [
                    "PUT",
                    "POST",
                    "DELETE",
                ];
            }
        }
    }

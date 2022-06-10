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

                $regionId = $addresses->addRegion($params["regionFiasId"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"]);

                error_log("                                    >$regionId<");

                return api::ANSWER($regionId, ($regionId !== false)?"regionId":"notAcceptable");
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

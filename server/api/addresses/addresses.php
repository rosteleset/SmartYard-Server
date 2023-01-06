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

                $regionId = @(int)$params["regionId"];
                $areaId = @(int)$params["areaId"];
                $cityId = @(int)$params["cityId"];
                $settlementId = @(int)$params["settlementId"];
                $streetId = @(int)$params["streetId"];

                $r = [
                    "regions" => $addresses->getRegions(),
                    "areas" => $addresses->getAreas($regionId),
                    "cities" => $addresses->getCities($regionId, $areaId),
                    "settlements" => $addresses->getSettlements($areaId, $cityId),
                    "streets" => $addresses->getStreets($cityId, $settlementId),
                    "houses" => $addresses->getHouses($settlementId, $streetId),
                ];

                return api::ANSWER($r, ($r !== false)?"addresses":"badRequest");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

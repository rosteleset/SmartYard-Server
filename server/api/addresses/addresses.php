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

                $include = @$params["include"];
                $include = $include?:"regions,areas,cities,settlements,streets,houses";

                $r = [];

                if (strpos($include, "regions") !== false) {
                    $r["regions"] = $addresses->getRegions();
                }

                if (strpos($include, "areas") !== false) {
                    $r["areas"] = $addresses->getAreas($regionId);
                }

                if (strpos($include, "cities") !== false) {
                    $r["cities"] = $addresses->getCities($regionId, $areaId);
                }

                if (strpos($include, "settlements") !== false) {
                    $r["settlements"] = $addresses->getSettlements($areaId, $cityId);
                }

                if (strpos($include, "streets") !== false) {
                    $r["streets"] = $addresses->getStreets($cityId, $settlementId);
                }

                if (strpos($include, "houses") !== false) {
                    $r["houses"] = $addresses->getHouses($settlementId, $streetId);
                }

                return api::ANSWER($r, ($r !== false)?"addresses":"badRequest");
            }

            public static function index() {
                return [
                    "GET" => "#common",
                ];
            }
        }
    }

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
                $houseId = @(int)$params["houseId"];

                $include = @$params["include"]?:"regions,areas,cities,settlements,streets,houses";

                $r = [];

                if (strpos($include, "regions") !== false) {
                    $r["regions"] = $addresses->getRegions();
                }

                if (strpos($include, "areas") !== false) {
                    if ($areaId) {
                        $r["areas"] = [ $addresses->getArea($areaId) ];
                    } else {
                        $r["areas"] = $addresses->getAreas($regionId);
                    }
                }

                if (strpos($include, "cities") !== false) {
                    if ($cityId) {
                        $r["cities"] = [ $addresses->getCity($cityId) ];
                    } else {
                        $r["cities"] = $addresses->getCities($regionId, $areaId);
                    }
                }

                if (strpos($include, "settlements") !== false) {
                    if ($settlementId) {
                        $r["settlements"] = [ $addresses->getSettlement($settlementId) ];
                    } else {
                        $r["settlements"] = $addresses->getSettlements($areaId, $cityId);
                    }
                }

                if (strpos($include, "streets") !== false) {
                    if ($streetId) {
                        $r["streets"] = [ $addresses->getStreet($streetId) ];
                    } else {
                        $r["streets"] = $addresses->getStreets($cityId, $settlementId);
                    }
                }

                if (strpos($include, "houses") !== false) {
                    if ($houseId) {
                        $r["houses"] = [ $addresses->getHouse($houseId) ];
                    } else {
                        $r["houses"] = $addresses->getHouses($settlementId, $streetId);
                    }
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

<?php

    /**
     * @api {get} /api/addresses/addresses get addresses
     *
     * @apiVersion 1.0.0
     *
     * @apiName addresses
     * @apiGroup addresses
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {Number} [regionId] regionId
     * @apiBody {Number} [areaId] areaId
     * @apiBody {Number} [cityId] cityId
     * @apiBody {Number} [settlementId] settlementId
     * @apiBody {Number} [streetId] streetId
     * @apiBody {Number} [houseId] houseId
     * @apiBody {String} [include="regions,areas,cities,settlements,streets,houses"] include parent objects
     *
     * @apiSuccess {Object} list of address objects
     */

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

                $include = @$params["include"] ?: "regions,areas,cities,settlements,streets,houses";

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

                return api::ANSWER($r, ($r !== false) ? "addresses" : "badRequest");
            }

            public static function index() {
                $addresses = loadBackend("addresses");

                if ($addresses) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }

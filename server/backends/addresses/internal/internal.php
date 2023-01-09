<?php

    /**
     * backends addresses namespace
     */

    namespace backends\addresses
    {

        /**
         * internal.db addresses class
         */
        class internal extends addresses
        {

            /**
             * @inheritDoc
             */
            function getRegions()
            {
                return $this->db->get("select address_region_id, region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region from addresses_regions order by region", false, [
                    "address_region_id" => "regionId",
                    "region_uuid" => "regionUuid",
                    "region_iso_code" => "regionIsoCode",
                    "region_with_type" => "regionWithType",
                    "region_type" => "regionType",
                    "region_type_full" => "regionTypeFull",
                    "region" => "region",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getRegion($regionId)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                return $this->db->get(
                    "select address_region_id, region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region from addresses_regions where address_region_id = :address_region_id",
                    [
                        ":address_region_id" => $regionId,
                    ],
                    [
                        "address_region_id" => "regionId",
                        "region_uuid" => "regionUuid",
                        "region_iso_code" => "regionIsoCode",
                        "region_with_type" => "regionWithType",
                        "region_type" => "regionType",
                        "region_type_full" => "regionTypeFull",
                        "region" => "region",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifyRegion($regionId, $regionUuid, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                if ($regionId && trim($regionWithType) && trim($region)) {
                    return $this->db->modify("update addresses_regions set region_uuid = :region_uuid, region_iso_code = :region_iso_code, region_with_type = :region_with_type, region_type = :region_type, region_type_full = :region_type_full, region = :region where address_region_id = $regionId", [
                        ":region_uuid" => $regionUuid,
                        ":region_iso_code" => $regionIsoCode,
                        ":region_with_type" => $regionWithType,
                        ":region_type" => $regionType,
                        ":region_type_full" => $regionTypeFull,
                        ":region" => $region,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addRegion($regionUuid, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region)
            {
                if (trim($regionWithType) && trim($region)) {
                    return $this->db->insert("insert into addresses_regions (region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region) values (:region_uuid, :region_iso_code, :region_with_type, :region_type, :region_type_full, :region)", [
                        ":region_uuid" => $regionUuid,
                        ":region_iso_code" => $regionIsoCode,
                        ":region_with_type" => $regionWithType,
                        ":region_type" => $regionType,
                        ":region_type_full" => $regionTypeFull,
                        ":region" => $region,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteRegion($regionId)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_regions where address_region_id = $regionId");
            }

            /**
             * @inheritDoc
             */
            function getAreas($regionId = false)
            {
                if ($regionId) {
                    if (!checkInt($regionId)) {
                        return false;
                    }
                    $query = "select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area from addresses_areas where address_region_id = $regionId order by area";
                } else {
                    $query = "select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area from addresses_areas order by area";
                }
                return $this->db->get($query, false, [
                    "address_area_id" => "areaId",
                    "address_region_id" => "regionId",
                    "area_uuid" => "areaUuid",
                    "area_with_type" => "areaWithType",
                    "area_type" => "areaType",
                    "area_type_full" => "areaTypeFull",
                    "area" => "area",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getArea($areaId)
            {
                if (!checkInt($areaId)) {
                    return false;
                }

                return $this->db->get("select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area from addresses_areas where address_area_id = $areaId", false,
                    [
                        "address_area_id" => "areaId",
                        "address_region_id" => "regionId",
                        "area_uuid" => "areaUuid",
                        "area_with_type" => "areaWithType",
                        "area_type" => "areaType",
                        "area_type_full" => "areaTypeFull",
                        "area" => "area",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifyArea($areaId, $regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                if (!checkInt($areaId)) {
                    return false;
                }

                if (!checkInt($regionId)) {
                    return false;
                }

                if ($areaId && trim($areaWithType) && trim($area)) {
                    return $this->db->modify("update addresses_areas set address_region_id = :address_region_id, area_uuid = :area_uuid, area_with_type = :area_with_type, area_type = :area_type, area_type_full = :area_type_full, area = :area where address_area_id = $areaId", [
                        ":address_region_id" => $regionId,
                        ":area_uuid" => $areaUuid,
                        ":area_with_type" => $areaWithType,
                        ":area_type" => $areaType,
                        ":area_type_full" => $areaTypeFull,
                        ":area" => $area,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addArea($regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                if (trim($areaWithType) && trim($area)) {
                    return $this->db->insert("insert into addresses_areas (address_region_id, area_uuid, area_with_type, area_type, area_type_full, area) values (:address_region_id, :area_uuid, :area_with_type, :area_type, :area_type_full, :area)", [
                        ":address_region_id" => $regionId?:null,
                        ":area_uuid" => $areaUuid,
                        ":area_with_type" => $areaWithType,
                        ":area_type" => $areaType,
                        ":area_type_full" => $areaTypeFull,
                        ":area" => $area,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteArea($areaId)
            {
                if (!checkInt($areaId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_areas where address_area_id = $areaId");
            }

            /**
             * @inheritDoc
             */
            function getCities($regionId = false, $areaId = false)
            {
                if ($regionId && $areaId) {
                    return false;
                }

                if ($regionId && !checkInt($regionId)) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if ($regionId) {
                    $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city from addresses_cities where address_region_id = $regionId and coalesce(address_area_id, 0) = 0 order by city";
                } else
                if ($areaId) {
                    $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city from addresses_cities where address_area_id = $areaId and coalesce(address_region_id, 0) = 0 order by city";
                } else {
                    $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city from addresses_cities order by city";
                }

                return $this->db->get($query, false, [
                    "address_city_id" => "cityId",
                    "address_region_id" => "regionId",
                    "address_area_id" => "areaId",
                    "city_uuid" => "cityUuid",
                    "city_with_type" => "cityWithType",
                    "city_type" => "cityType",
                    "city_type_full" => "cityTypeFull",
                    "city" => "city",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getCity($cityId)
            {
                if (!checkInt($cityId)) {
                    return false;
                }

                return $this->db->get("select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city from addresses_cities where address_city_id = $cityId", false,
                    [
                        "address_city_id" => "cityId",
                        "address_region_id" => "regionId",
                        "address_area_id" => "areaId",
                        "city_uuid" => "cityUuid",
                        "city_with_type" => "cityWithType",
                        "city_type" => "cityType",
                        "city_type_full" => "cityTypeFull",
                        "city" => "city",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifyCity($cityId, $regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city)
            {
                if (!checkInt($cityId)) {
                    return false;
                }

                if ($regionId && $areaId) {
                    return false;
                }

                if ($regionId && !checkInt($regionId)) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if (!$regionId && !$areaId) {
                    return false;
                }

                if (trim($cityWithType) && trim($city)) {
                    return $this->db->modify("update addresses_cities set address_region_id = :address_region_id, address_area_id = :address_area_id, city_uuid = :city_uuid, city_with_type = :city_with_type, city_type = :city_type, city_type_full = :city_type_full, city = :city where address_city_id = $cityId", [
                        ":address_region_id" => $regionId,
                        ":address_area_id" => $areaId,
                        ":city_uuid" => $cityUuid,
                        ":city_with_type" => $cityWithType,
                        ":city_type" => $cityType,
                        ":city_type_full" => $cityTypeFull,
                        ":city" => $city,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addCity($regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city)
            {
                if ($regionId && $areaId) {
                    return false;
                }

                if ($regionId && !checkInt($regionId)) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if (!$regionId && !$areaId) {
                    return false;
                }

                if (trim($cityWithType) && trim($city)) {
                    return $this->db->insert("insert into addresses_cities (address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city) values (:address_region_id, :address_area_id, :city_uuid, :city_with_type, :city_type, :city_type_full, :city)", [
                        ":address_region_id" => $regionId?:null,
                        ":address_area_id" => $areaId?:null,
                        ":city_uuid" => $cityUuid,
                        ":city_with_type" => $cityWithType,
                        ":city_type" => $cityType,
                        ":city_type_full" => $cityTypeFull,
                        ":city" => $city,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteCity($cityId)
            {
                if (!checkInt($cityId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_cities where address_city_id = $cityId");
            }

            /**
             * @inheritDoc
             */
            function getSettlements($areaId = false, $cityId = false)
            {
                if ($areaId && $cityId) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if ($areaId) {
                    $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_area_id = $areaId and coalesce(address_city_id, 0) = 0 order by settlement";
                } else
                if ($cityId) {
                    $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_city_id = $cityId and coalesce(address_area_id, 0) = 0 order by settlement";
                } else {
                    $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements order by settlement";
                }

                return $this->db->get($query, false, [
                    "address_settlement_id" => "settlementId",
                    "address_area_id" => "areaId",
                    "address_city_id" => "cityId",
                    "settlement_uuid" => "settlementUuid",
                    "settlement_with_type" => "settlementWithType",
                    "settlement_type" => "settlementType",
                    "settlement_type_full" => "settlementTypeFull",
                    "settlement" => "settlement",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getSettlement($settlementId)
            {
                if (!checkInt($settlementId)) {
                    return false;
                }

                return $this->db->get("select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_settlement_id = $settlementId", false,
                    [
                        "address_settlement_id" => "settlementId",
                        "address_area_id" => "areaId",
                        "address_city_id" => "cityId",
                        "settlement_uuid" => "settlementUuid",
                        "settlement_with_type" => "settlementWithType",
                        "settlement_type" => "settlementType",
                        "settlement_type_full" => "settlementTypeFull",
                        "settlement" => "settlement",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifySettlement($settlementId, $areaId, $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement)
            {
                if (!checkInt($settlementId)) {
                    return false;
                }

                if ($areaId && $cityId) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if (!$areaId && !$cityId) {
                    return false;
                }

                if (trim($settlementWithType) && trim($settlement)) {
                    return $this->db->modify("update addresses_settlements set address_area_id = :address_area_id, address_city_id = :address_city_id, settlement_uuid = :settlement_uuid, settlement_with_type = :settlement_with_type, settlement_type = :settlement_type, settlement_type_full = :settlement_type_full, settlement = :settlement where address_settlement_id = $settlementId", [
                        ":address_area_id" => $areaId,
                        ":address_city_id" => $cityId,
                        ":settlement_uuid" => $settlementUuid,
                        ":settlement_with_type" => $settlementWithType,
                        ":settlement_type" => $settlementType,
                        ":settlement_type_full" => $settlementTypeFull,
                        ":settlement" => $settlement,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addSettlement($areaId, $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement)
            {
                if ($areaId && $cityId) {
                    return false;
                }

                if ($areaId && !checkInt($areaId)) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if (!$areaId && !$cityId) {
                    return false;
                }

                if (trim($settlementWithType) && trim($settlement)) {
                    return $this->db->insert("insert into addresses_settlements (address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement) values (:address_area_id, :address_city_id, :settlement_uuid, :settlement_with_type, :settlement_type, :settlement_type_full, :settlement)", [
                        ":address_area_id" => $areaId?:null,
                        ":address_city_id" => $cityId?:null,
                        ":settlement_uuid" => $settlementUuid,
                        ":settlement_with_type" => $settlementWithType,
                        ":settlement_type" => $settlementType,
                        ":settlement_type_full" => $settlementTypeFull,
                        ":settlement" => $settlement,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteSettlement($settlementId)
            {
                if (!checkInt($settlementId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_settlements where address_settlement_id = $settlementId");
            }

            /**
             * @inheritDoc
             */
            function getStreets($cityId = false, $settlementId = false)
            {
                if ($cityId && $settlementId) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if ($cityId) {
                    $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_city_id = $cityId and coalesce(address_settlement_id, 0) = 0 order by street";
                } else
                if ($settlementId) {
                    $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_settlement_id = $settlementId and coalesce(address_city_id, 0) = 0 order by street";
                } else {
                    $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets order by street";
                }
                return $this->db->get($query, false, [
                    "address_street_id" => "streetId",
                    "address_city_id" => "cityId",
                    "address_settlement_id" => "settlementId",
                    "street_uuid" => "streetUuid",
                    "street_with_type" => "streetWithType",
                    "street_type" => "streetType",
                    "street_type_full" => "streetTypeFull",
                    "street" => "street",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getStreet($streetId)
            {
                if (!checkInt($streetId)) {
                    return false;
                }

                return $this->db->get("select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_street_id = $streetId", false,
                    [
                        "address_street_id" => "streetId",
                        "address_city_id" => "cityId",
                        "address_settlement_id" => "settlementId",
                        "street_uuid" => "streetUuid",
                        "street_with_type" => "streetWithType",
                        "street_type" => "streetType",
                        "street_type_full" => "streetTypeFull",
                        "street" => "street",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifyStreet($streetId, $cityId, $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street)
            {
                if (!checkInt($streetId)) {
                    return false;
                }

                if ($cityId && $settlementId) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if (!$cityId && !$settlementId) {
                    return false;
                }

                if (trim($streetWithType) && trim($street)) {
                    return $this->db->modify("update addresses_streets set address_city_id = :address_city_id, address_settlement_id = :address_settlement_id, street_uuid = :street_uuid, street_with_type = :street_with_type, street_type = :street_type, street_type_full = :street_type_full, street = :street where address_street_id = $streetId", [
                        ":address_city_id" => $cityId,
                        ":address_settlement_id" => $settlementId,
                        ":street_uuid" => $streetUuid,
                        ":street_with_type" => $streetWithType,
                        ":street_type" => $streetType,
                        ":street_type_full" => $streetTypeFull,
                        ":street" => $street,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addStreet($cityId, $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street)
            {
                if ($cityId && $settlementId) {
                    return false;
                }

                if ($cityId && !checkInt($cityId)) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if (!$cityId && !$settlementId) {
                    return false;
                }

                if (trim($streetWithType) && trim($street)) {
                    return $this->db->insert("insert into addresses_streets (address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street) values (:address_city_id, :address_settlement_id, :street_uuid, :street_with_type, :street_type, :street_type_full, :street)", [
                        ":address_city_id" => $cityId?:null,
                        ":address_settlement_id" => $settlementId?:null,
                        ":street_uuid" => $streetUuid,
                        ":street_with_type" => $streetWithType,
                        ":street_type" => $streetType,
                        ":street_type_full" => $streetTypeFull,
                        ":street" => $street,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteStreet($streetId)
            {
                if (!checkInt($streetId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_streets where address_street_id = $streetId");
            }

            /**
             * @inheritDoc
             */
            function getHouses($settlementId = false, $streetId = false)
            {
                if ($settlementId && $streetId) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if ($streetId && !checkInt($streetId)) {
                    return false;
                }

                if ($settlementId) {
                    $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_settlement_id = $settlementId and coalesce(address_street_id, 0) = 0 order by house";
                } else
                if ($streetId) {
                    $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_street_id = $streetId and coalesce(addresses_houses.address_settlement_id, 0) = 0 order by house";
                } else {
                    $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses order by house";
                }

                return $this->db->get($query, false, [
                    "address_house_id" => "houseId",
                    "address_settlement_id" => "settlementId",
                    "address_street_id" => "streetId",
                    "house_uuid" => "houseUuid",
                    "house_type" => "houseType",
                    "house_type_full" => "houseTypeFull",
                    "house_full" => "houseFull",
                    "house" => "house",
                ]);
            }

            /**
             * @inheritDoc
             */
            function getHouse($houseId)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                return $this->db->get("select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_house_id = $houseId", false,
                    [
                        "address_house_id" => "houseId",
                        "address_settlement_id" => "settlementId",
                        "address_street_id" => "streetId",
                        "house_uuid" => "houseUuid",
                        "house_type" => "houseType",
                        "house_type_full" => "houseTypeFull",
                        "house_full" => "houseFull",
                        "house" => "house",
                    ],
                    [
                        "singlify"
                    ]
                );
            }

            /**
             * @inheritDoc
             */
            function modifyHouse($houseId, $settlementId, $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                if ($settlementId && $streetId) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if ($streetId && !checkInt($streetId)) {
                    return false;
                }

                if (!$settlementId && !$streetId) {
                    return false;
                }

                if (trim($houseFull) && trim($house)) {
                    return $this->db->modify("update addresses_houses set address_settlement_id = :address_settlement_id, address_street_id = :address_street_id, house_uuid = :house_uuid, house_type = :house_type, house_type_full = :house_type_full, house_full = :house_full, house = :house where address_house_id = $houseId", [
                        ":address_settlement_id" => $settlementId,
                        ":address_street_id" => $streetId,
                        ":house_uuid" => $houseUuid,
                        ":house_type" => $houseType,
                        ":house_type_full" => $houseTypeFull,
                        ":house_full" => $houseFull,
                        ":house" => $house,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function addHouse($settlementId, $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house)
            {
                if ($settlementId && $streetId) {
                    return false;
                }

                if ($settlementId && !checkInt($settlementId)) {
                    return false;
                }

                if ($streetId && !checkInt($streetId)) {
                    return false;
                }

                if (!$settlementId && !$streetId) {
                    return false;
                }

                if (trim($houseFull) && trim($house)) {
                    return $this->db->insert("insert into addresses_houses (address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house) values (:address_settlement_id, :address_street_id, :house_uuid, :house_type, :house_type_full, :house_full, :house)", [
                        ":address_settlement_id" => $settlementId?:null,
                        ":address_street_id" => $streetId?:null,
                        ":house_uuid" => $houseUuid,
                        ":house_type" => $houseType,
                        ":house_type_full" => $houseTypeFull,
                        ":house_full" => $houseFull,
                        ":house" => $house,
                    ]);
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function deleteHouse($houseId)
            {
                if (!checkInt($houseId)) {
                    return false;
                }

                return $this->db->modify("delete from addresses_houses where address_house_id = $houseId");
            }

            /**
             * @inheritDoc
             */
            function addHouseByMagic($houseUuid)
            {
                $house = $this->redis->get("house_" . $houseUuid);

                if ($house) {
                    $house = json_decode($house, true);

                    $regionId = null;

                    if ($house["data"]["region_fias_id"]) {
                        $regionId = $this->db->get("select address_region_id from addresses_regions where region_uuid = :region_uuid or region = :region",
                            [
                                "region_uuid" => $house["data"]["region_fias_id"],
                                "region" => $house["data"]["region"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$regionId) {
                            $regionId = $this->addRegion($house["data"]["region_fias_id"], $house["data"]["region_iso_code"], $house["data"]["region_with_type"], $house["data"]["region_type"], $house["data"]["region_type_full"], $house["data"]["region"]);
                        }
                    }

                    if (!$regionId) {
                        error_log($house["data"]["house_fias_id"] . " no region");
                        return false;
                    }

                    $areaId = null;
                    if ($house["data"]["area_fias_id"]) {
                        $areaId = $this->db->get("select address_area_id from addresses_areas where area_uuid = :area_uuid or (address_region_id = :address_region_id and area = :area)",
                            [
                                "area_uuid" => $house["data"]["area_fias_id"],
                                "address_region_id" => $regionId,
                                "area" => $house["data"]["area"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$areaId) {
                            $areaId = $this->addArea($regionId, $house["data"]["area_fias_id"], $house["data"]["area_with_type"], $house["data"]["area_type"], $house["data"]["area_type_full"], $house["data"]["area"]);
                        }
                    }

                    if ($areaId) {
                        $regionId = null;
                    }

                    $cityId = null;
                    if ($house["data"]["city_fias_id"]) {
                        $cityId = $this->db->get("select address_city_id from addresses_cities where city_uuid = :city_uuid or (address_region_id = :address_region_id and city = :city) or (address_area_id = :address_area_id and city = :city)",
                            [
                                "city_uuid" => $house["data"]["city_fias_id"],
                                "address_region_id" => $regionId,
                                "address_area_id" => $areaId,
                                "city" => $house["data"]["city"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$cityId) {
                            $cityId = $this->addCity($regionId, $areaId, $house["data"]["city_fias_id"], $house["data"]["city_with_type"], $house["data"]["city_type"], $house["data"]["city_type_full"], $house["data"]["city"]);
                        }
                    }

                    if ($cityId) {
                        $areaId = null;
                    }

                    if (!$areaId && !$cityId) {
                        error_log($house["data"]["house_fias_id"] . " no area or city");
                        return false;
                    }

                    $settlementId = null;
                    if ($house["data"]["settlement_fias_id"]) {
                        $settlementId = $this->db->get("select address_settlement_id from addresses_settlements where settlement_uuid = :settlement_uuid or (address_area_id = :address_area_id and settlement = :settlement) or (address_city_id = :address_city_id and settlement = :settlement)",
                            [
                                "settlement_uuid" => $house["data"]["settlement_fias_id"],
                                "address_area_id" => $areaId,
                                "address_city_id" => $cityId,
                                "settlement" => $house["data"]["settlement"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$settlementId) {
                            $settlementId = $this->addSettlement($areaId, $cityId, $house["data"]["settlement_fias_id"], $house["data"]["settlement_with_type"], $house["data"]["settlement_type"], $house["data"]["settlement_type_full"], $house["data"]["settlement"]);
                        }
                    }

                    if ($settlementId) {
                        $cityId = null;
                    }

                    if (!$cityId && !$settlementId) {
                        error_log($house["data"]["house_fias_id"] . " no city or settlement");
                        return false;
                    }

                    $streetId = null;
                    if ($house["data"]["street_fias_id"]) {
                        $streetId = $this->db->get("select address_street_id from addresses_streets where street_uuid = :street_uuid or (address_city_id = :address_city_id and street = :street) or (address_settlement_id = :address_settlement_id and street = :street)",
                            [
                                "street_uuid" => $house["data"]["street_fias_id"],
                                "address_city_id" => $cityId,
                                "address_settlement_id" => $settlementId,
                                "street" => $house["data"]["street"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$streetId) {
                            $streetId = $this->addStreet($cityId, $settlementId, $house["data"]["street_fias_id"], $house["data"]["street_with_type"], $house["data"]["street_type"], $house["data"]["street_type_full"], $house["data"]["street"]);
                        }
                    }

                    if ($streetId) {
                        $settlementId = null;
                    }

                    if (!$settlementId && !$streetId) {
                        error_log($house["data"]["house_fias_id"] . " no setllement or street");
                        return false;
                    }

                    $houseId = null;
                    if ($house["data"]["house_fias_id"]) {
                        $houseId = $this->db->get("select address_house_id from addresses_houses where house_uuid = :house_uuid or (address_settlement_id = :address_settlement_id and house = :house) or (address_street_id = :address_street_id and house = :house)",
                            [
                                "house_uuid" => $house["data"]["house_fias_id"],
                                "address_settlement_id" => $settlementId,
                                "address_street_id" => $streetId,
                                "house" => $house["data"]["house"],
                            ],
                            false,
                            [
                                "fieldlify"
                            ]
                        );
                        if (!$houseId) {
                            $houseId = $this->addHouse($settlementId, $streetId, $house["data"]["house_fias_id"], $house["data"]["house_type"], $house["data"]["house_type_full"], $house["value"], $house["data"]["house"]);
                        }
                    }

                    if ($houseId) {
                        return $houseId;
                    } else {
                        error_log($house["data"]["house_fias_id"] . " no house");
                        return false;
                    }
                } else {
                    return false;
                }
            }

            /**
             * @inheritDoc
             */
            function cleanup()
            {
                $n = 0;

                $n += $this->db->modify("delete from addresses_houses where address_settlement_id is not null and address_settlement_id not in (select address_settlement_id from addresses_settlements)");
                $n += $this->db->modify("delete from addresses_houses where address_street_id is not null and address_street_id not in (select address_street_id from addresses_streets)");
                $n += $this->db->modify("delete from addresses_houses where address_street_id is null and address_settlement_id is null");

                $n += $this->db->modify("delete from addresses_streets where address_city_id is not null and address_city_id not in (select address_city_id from addresses_cities)");
                $n += $this->db->modify("delete from addresses_streets where address_settlement_id is not null and address_settlement_id not in (select address_settlement_id from addresses_settlements)");
                $n += $this->db->modify("delete from addresses_streets where address_settlement_id is null and address_city_id is null");

                $n += $this->db->modify("delete from addresses_settlements where address_area_id is not null and address_area_id not in (select address_area_id from addresses_areas)");
                $n += $this->db->modify("delete from addresses_settlements where address_city_id is not null and address_city_id not in (select address_city_id from addresses_cities)");
                $n += $this->db->modify("delete from addresses_settlements where address_area_id is null and address_city_id is null");

                $n += $this->db->modify("delete from addresses_cities where address_region_id is not null and address_region_id not in (select address_region_id from addresses_regions)");
                $n += $this->db->modify("delete from addresses_cities where address_area_id is not null and address_area_id not in (select address_area_id from addresses_areas)");
                $n += $this->db->modify("delete from addresses_cities where address_region_id is null and address_area_id is null");

                $n += $this->db->modify("delete from addresses_areas where address_region_id is not null and address_region_id not in (select address_region_id from addresses_regions)");
                $n += $this->db->modify("delete from addresses_areas where address_region_id is null");

                return $n;
            }

            /**
             * @inheritDoc
             */
            function cron($part)
            {
                if ($part === "5min") {
                    $this->cleanup();

                    return true;
                }
            }

        }
    }

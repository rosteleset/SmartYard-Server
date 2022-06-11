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
                return $this->db->get("select address_region_id, region_fias_id, region_iso_code, region_with_type, region_type, region_type_full, region from addresses_regions order by region", false, [
                    "address_region_id" => "regionId",
                    "region_fias_id" => "regionFiasId",
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
                    "select address_region_id, region_fias_id, region_iso_code, region_with_type, region_type, region_type_full, region from addresses_regions where address_region_id = :address_region_id",
                    [
                        ":address_region_id" => $regionId,
                    ],
                    [
                        "address_region_id" => "regionId",
                        "region_fias_id" => "regionFiasId",
                        "region_iso_code" => "regionIsoCode",
                        "region_with_type" => "regionWithType",
                        "region_type" => "regionType",
                        "region_type_full" => "regionTypeFull",
                        "region" => "region",
                    ],
                    true
                );
            }

            /**
             * @inheritDoc
             */
            function modifyRegion($regionId, $regionFiasId, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                if ($regionId && trim($regionWithType) && trim($region)) {
                    return $this->db->modify("update addresses_regions set region_fias_id = :region_fias_id, region_iso_code = :region_iso_code, region_with_type = :region_with_type, region_type = :region_type, region_type_full = :region_type_full, region = :region where address_region_id = $regionId", [
                        ":region_fias_id" => $regionFiasId,
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
            function addRegion($regionFiasId, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region)
            {
                if (trim($regionWithType) && trim($region)) {
                    return $this->db->insert("insert into addresses_regions (region_fias_id, region_iso_code, region_with_type, region_type, region_type_full, region) values (:region_fias_id, :region_iso_code, :region_with_type, :region_type, :region_type_full, :region)", [
                        ":region_fias_id" => $regionFiasId,
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
                    $query = "select address_area_id, address_region_id, area_fias_id, area_with_type, area_type, area_type_full, area from addresses_areas where address_region_id = $regionId order by area";
                } else {
                    $query = "select address_area_id, address_region_id, area_fias_id, area_with_type, area_type, area_type_full, area from addresses_areas order by area";
                }
                return $this->db->get($query, false, [
                    "address_area_id" => "areaId",
                    "address_region_id" => "regionId",
                    "area_fias_id" => "areaFiasId",
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

                return $this->db->get("select address_area_id, address_region_id, area_fias_id, area_with_type, area_type, area_type_full, area from addresses_areas where address_area_id = $areaId", false, [
                    "address_area_id" => "areaId",
                    "address_region_id" => "regionId",
                    "area_fias_id" => "areaFiasId",
                    "area_with_type" => "areaWithType",
                    "area_type" => "areaType",
                    "area_type_full" => "areaTypeFull",
                    "area" => "area",
                ], true);
            }

            /**
             * @inheritDoc
             */
            function modifyArea($areaId, $regionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                if (!checkInt($areaId)) {
                    return false;
                }

                if ($areaId && trim($areaWithType) && trim($area)) {
                    return $this->db->modify("update addresses_areas set address_region_id = :address_region_id, area_fias_id = :area_fias_id, area_with_type = :area_with_type, area_type = :area_type, area_type_full = :area_type_full, area = :area where address_area_id = $areaId", [
                        ":address_region_id" => $regionId,
                        ":area_fias_id" => $areaFiasId,
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
            function addArea($regionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                if (!checkInt($regionId)) {
                    return false;
                }

                if (trim($areaWithType) && trim($area)) {
                    return $this->db->insert("insert into addresses_areas (address_region_id, area_fias_id, area_with_type, area_type, area_type_full, area) values (:address_region_id, :area_fias_id, :area_with_type, :area_type, :area_type_full, :area)", [
                        ":address_region_id" => $regionId,
                        ":area_fias_id" => $areaFiasId,
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
            function getCities($regionId, $areaId)
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
                    $query = "select address_city_id, address_region_id, address_area_id, city_fias_id, city_with_type, city_type, city_type_full, city from addresses_cities where address_region_id = $regionId and address_area_id is null order by city";
                } else
                if ($areaId) {
                    $query = "select address_city_id, address_region_id, address_area_id, city_fias_id, city_with_type, city_type, city_type_full, city from addresses_cities where address_area_id = $areaId and addresses_cities.address_region_id is null order by city";
                } else {
                    $query = "select address_city_id, address_region_id, address_area_id, city_fias_id, city_with_type, city_type, city_type_full, city from addresses_cities order by city";
                }

                return $this->db->get($query, false, [
                    "address_city_id" => "cityId",
                    "address_region_id" => "regionId",
                    "address_area_id" => "areaId",
                    "city_fias_id" => "cityFiasId",
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

                return $this->db->get("select address_city_id, address_region_id, address_area_id, city_fias_id, city_with_type, city_type, city_type_full, city from addresses_cities where address_city_id = $cityId", false, [
                    "address_city_id" => "cityId",
                    "address_region_id" => "regionId",
                    "address_area_id" => "areaId",
                    "city_fias_id" => "cityFiasId",
                    "city_with_type" => "cityWithType",
                    "city_type" => "cityType",
                    "city_type_full" => "cityTypeFull",
                    "city" => "city",
                ], true);
            }

            /**
             * @inheritDoc
             */
            function modifyCity($cityId, $regionId, $areaId, $cityFiasId, $cityWithType, $cityType, $cityTypeFull, $city)
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
                    return $this->db->modify("update addresses_cities set address_region_id = :address_region_id, address_area_id = :address_area_id, city_fias_id = :city_fias_id, city_with_type = :city_with_type, city_type = :city_type, city_type_full = :city_type_full, city = :city where address_city_id = $cityId", [
                        ":address_region_id" => $regionId,
                        ":address_area_id" => $areaId,
                        ":city_fias_id" => $cityFiasId,
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
            function addCity($regionId, $areaId, $cityFiasId, $cityWithType, $cityType, $cityTypeFull, $city)
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
                    return $this->db->insert("insert into addresses_cities (address_region_id, address_area_id, city_fias_id, city_with_type, city_type, city_type_full, city) values (:address_region_id, :address_area_id, :city_fias_id, :city_with_type, :city_type, :city_type_full, :city)", [
                        ":address_region_id" => $regionId,
                        ":address_area_id" => $areaId,
                        ":city_fias_id" => $cityFiasId,
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
        }
    }

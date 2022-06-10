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
                // TODO: Implement getRegion() method.
            }

            /**
             * @inheritDoc
             */
            function modifyRegion($regionId, $regionFiasId, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region)
            {
                // TODO: Implement modifyRegion() method.
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
                // TODO: Implement deleteRegion() method.
            }

            /**
             * @inheritDoc
             */
            function getAreas($regionId)
            {
                // TODO: Implement getAreas() method.
            }

            /**
             * @inheritDoc
             */
            function getArea($areaId)
            {
                // TODO: Implement getArea() method.
            }

            /**
             * @inheritDoc
             */
            function modifyArea($areaId, $addressRegionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                // TODO: Implement modifyArea() method.
            }

            /**
             * @inheritDoc
             */
            function addArea($addressRegionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area)
            {
                // TODO: Implement addArea() method.
            }

            /**
             * @inheritDoc
             */
            function deleteArea($areaId)
            {
                // TODO: Implement deletearea() method.
            }
        }
    }

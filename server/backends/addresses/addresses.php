<?php

    /**
    * backends addresses namespace
    */

    namespace backends\addresses
    {

        use backends\backend;

        /**
         * base addresses class
         */
        abstract class addresses extends backend
        {

            /**
             * @return false|array
             */
            abstract function getRegions();

            /**
             * @param integer $regionId
             * @return false|arrau
             */
            abstract function getRegion($regionId);

            /**
             * @param $regionId
             * @param $regionUuid
             * @param $regionIsoCode
             * @param $regionWithType
             * @param $regionType
             * @param $regionTypeFull
             * @param $region
             * @return boolean
             */
            abstract function modifyRegion($regionId, $regionUuid, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region);

            /**
             * @param $regionUuid
             * @param $regionIsoCode
             * @param $regionWithType
             * @param $regionType
             * @param $regionTypeFull
             * @param $region
             * @return false|integer
             */
            abstract function addRegion($regionUuid, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region);

            /**
             * @param $regionId
             * @return boolean
             */
            abstract function deleteRegion($regionId);

            /**
             * @param $regionId
             * @return false|array
             */
            abstract function getAreas($regionId);

            /**
             * @param $areaId
             * @return false|array
             */
            abstract function getArea($areaId);

            /**
             * @param $areaId
             * @param $regionId
             * @param $areaUuid
             * @param $areaWithType
             * @param $areaType
             * @param $areaTypeFull
             * @param $area
             * @return boolean
             */
            abstract function modifyArea($areaId, $regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area);

            /**
             * @param $regionId
             * @param $areaUuid
             * @param $areaWithType
             * @param $areaType
             * @param $areaTypeFull
             * @param $area
             * @return false|integer
             */
            abstract function addArea($regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area);

            /**
             * @param $areaId
             * @return boolean
             */
            abstract function deleteArea($areaId);

            /**
             * @param $regionId
             * @param $areaId
             * @return false|array
             */
            abstract function getCities($regionId = false, $areaId = false);

            /**
             * @param $cityId
             * @return false|array
             */
            abstract function getCity($cityId);

            /**
             * @param $cityId
             * @param $regionId
             * @param $areaId
             * @param $cityUuid
             * @param $cityWithType
             * @param $cityType
             * @param $cityTypeFull
             * @param $city
             * @return boolean
             */
            abstract function modifyCity($cityId, $regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city);

            /**
             * @param $regionId
             * @param $areaId
             * @param $cityUuid
             * @param $cityWithType
             * @param $cityType
             * @param $cityTypeFull
             * @param $city
             * @return false|integer
             */
            abstract function addCity($regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city);

            /**
             * @param $cityId
             * @return boolean
             */
            abstract function deleteCity($cityId);

        }
    }

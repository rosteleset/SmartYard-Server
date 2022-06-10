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
             * @param $regionFiasId
             * @param $regionIsoCode
             * @param $regionWithType
             * @param $regionType
             * @param $regionTypeFull
             * @param $region
             * @return boolean
             */
            abstract function modifyRegion($regionId, $regionFiasId, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region);

            /**
             * @param $regionFiasId
             * @param $regionIsoCode
             * @param $regionWithType
             * @param $regionType
             * @param $regionTypeFull
             * @param $region
             * @return false|integer
             */
            abstract function addRegion($regionFiasId, $regionIsoCode, $regionWithType, $regionType, $regionTypeFull, $region);

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
             * @param $addressRegionId
             * @param $areaFiasId
             * @param $areaWithType
             * @param $areaType
             * @param $areaTypeFull
             * @param $area
             * @return boolean
             */
            abstract function modifyArea($areaId, $addressRegionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area);

            /**
             * @param $addressRegionId
             * @param $areaFiasId
             * @param $areaWithType
             * @param $areaType
             * @param $areaTypeFull
             * @param $area
             * @return false|integer
             */
            abstract function addArea($addressRegionId, $areaFiasId, $areaWithType, $areaType, $areaTypeFull, $area);

            /**
             * @param $areaId
             * @return boolean
             */
            abstract function deleteArea($areaId);

        }
    }

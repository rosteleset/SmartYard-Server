<?php

    /**
    * backends houses namespace
    */

    namespace backends\houses
    {

        use backends\backend;

        /**
         * base addresses class
         */
        abstract class houses extends backend
        {

            /**
             * @param $houseId
             * @return false|array
             */
            abstract function getHouseFlats($houseId);

            /**
             * @param $houseId
             * @return false|array
             */
            abstract function getHouseEntrances($houseId);

            /**
             * @param $houseId
             * @param $entranceType
             * @param $entrance
             * @param $shared
             * @param $lat
             * @param $lon
             * @return boolean|integer
             */
            abstract function createEntrance($houseId, $entranceType, $entrance, $shared, $lat, $lon);

            /**
             * @param $houseId
             * @param $entranceId
             * @return boolean
             */
            abstract function addEntrance($houseId, $entranceId);

            /**
             * @param $entranceId
             * @param $entranceType
             * @param $entrance
             * @param $shared
             * @param $lat
             * @param $lon
             * @return boolean
             */
            abstract function modifyEntrance($entranceId, $entranceType, $entrance, $shared, $lat, $lon);

            /**
             * @param $houseId
             * @param $entranceId
             * @return boolean
             */
            abstract function removeEntrance($houseId, $entranceId);

            /**
             * @param $houseId
             * @param $floor
             * @param $flat
             * @param $entrances
             * @return boolean|integer
             */
            abstract function addFlat($houseId, $floor, $flat, $entrances);

            /**
             * @param $flatId
             * @param $floor
             * @param $flat
             * @return boolean
             */
            abstract function modifyFlat($flatId, $floor, $flat);

            /**
             * @param $flatId
             * @return boolean
             */
            abstract function deleteFlat($flatId);
        }
    }

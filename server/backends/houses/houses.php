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
            abstract function getHouse($houseId);

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
        }
    }

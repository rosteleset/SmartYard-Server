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
             * @return boolean
             */
            abstract function modifyHouse($houseId);
        }
    }

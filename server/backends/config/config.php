<?php

    /**
     * backends config namespace
     */

    namespace backends\config {

        use backends\backend;

        /**
         * base config class
         */

        abstract class config extends backend {
            /**
             * @return mixed
             */
            abstract public function getDomophonesModels();

            /**
             * @return false|array
             */
            abstract public function getCamerasModels();

            /**
             * @return mixed
             */
            abstract public function getAsteriskServers();

            /**
             * @return mixed
             */
            abstract public function getFRSServers();
        }
    }
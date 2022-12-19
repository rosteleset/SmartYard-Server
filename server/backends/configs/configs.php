<?php

    /**
     * backends configs namespace
     */

    namespace backends\configs {

        use backends\backend;

        /**
         * base configs class
         */

        abstract class configs extends backend {
            /**
             * @return mixed
             */
            abstract public function getDomophonesModels();

            /**
             * @return false|array
             */
            abstract public function getCamerasModels();

            /**
             * @return false|array
             */
            abstract public function getCMSes();
        }
    }
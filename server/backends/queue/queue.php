<?php

    /**
    * backends queue namespace
    */

    namespace backends\queue
    {

        use backends\backend;

        /**
         * base processes class
         */
        abstract class queue extends backend
        {
            /**
             * @param $objectType
             * @param $objectId
             * @return mixed
             */
            abstract public function changed($objectType, $objectId);

            /**
             * @return mixed
             */
            abstract public function autoconfigureDevices();

            /**
             * @return mixed
             */
            abstract public function wait();
        }
    }

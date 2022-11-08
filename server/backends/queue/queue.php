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
            abstract function changed($objectType, $objectId);
        }
    }

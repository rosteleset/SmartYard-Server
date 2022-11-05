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
             * @param $object_type
             * @param $object_id
             * @return mixed
             */
            abstract function changed($object_type, $object_id);

            /**
             * @param $object_type
             * @param $object_id
             * @param $task
             * @param $params
             * @return mixed
             */
            abstract function addToQueue($object_type, $object_id, $task, $params);
        }
    }

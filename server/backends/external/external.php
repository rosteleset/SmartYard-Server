<?php

    /**
     * backends external namespace
     */

    namespace backends\external {

        use backends\backend;

        /**
         * base external class
         */

        abstract class external extends backend {

            /**
             * @param string $external_id
             * @param string $type
             *
             * @return mixed
             */

             abstract public function external2internal($external_id, $type);

            /**
             * @param string $internal_id
             * @param string $type
             *
             * @return mixed
             */

             abstract public function internal2external($internal_id, $type);

             /**
             * @param string $external_id
             * @param string $internal_id
             * @param string $type
             *
             * @return mixed
             */

            abstract public function set($external_id, $internal_id, $type);
        }
    }
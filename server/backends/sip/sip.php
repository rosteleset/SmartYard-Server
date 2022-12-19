<?php

    /**
    * backends sip namespace
    */

    namespace backends\sip
    {

        use backends\backend;

        /**
         * base sip class
         */
        abstract class sip extends backend
        {

            /**
             * @param $by
             * @param $query
             * @return mixed
             */
            abstract public function server($by, $query = false);

            /**
             * @param $extension
             * @return mixed
             */
            abstract public function stun($extension);
        }
    }

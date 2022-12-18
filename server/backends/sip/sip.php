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
            abstract public function sipServer($by, $query);
        }
    }

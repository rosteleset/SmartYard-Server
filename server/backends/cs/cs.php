<?php

    /**
     * backends cs namespace
     */

    namespace backends\cs {

        use backends\backend;

        /**
         * base cs class
         */

        abstract class cs extends backend {
            /**
             * @return mixed
             */
            abstract public function getCS($sheet, $date);

            /**
             * @return false|array
             */
            abstract public function putCS($sheet, $date, $data);

            /**
             * @return false|array
             */
            abstract public function getCSes();
        }
    }
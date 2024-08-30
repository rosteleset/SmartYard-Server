<?php

    /**
     * backends dr namespace
     */

    namespace backends\cdr {

        use backends\backend;

        /**
         * base cdr class
         */

        abstract class cdr extends backend {

            /**
             * @param $phones
             * @param $dateFrom
             * @param $dateTo
             * @return mixed
             */

            abstract public function getCDR($phones, $dateFrom, $dateTo);
        }
    }
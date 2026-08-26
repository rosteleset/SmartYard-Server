<?php

    /**
     * backends statistics namespace
     */

    namespace backends\statistics {

        use backends\backend;

        /**
         * base statistics class
         */

        abstract class statistics extends backend {

            /**
             * @return array
             */

            abstract public function statistics();
        }
    }

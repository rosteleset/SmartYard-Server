<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        use backends\backend;

        /**
         * base mkb class
         */

        abstract class mkb extends backend {

            /**
             * @return mixed
             */

            abstract public function getDecks();
        }
    }
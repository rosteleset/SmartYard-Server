<?php

    /**
    * backends domophones namespace
    */

    namespace backends\domophones
    {

        use backends\backend;

        /**
         * base domophones class
         */
        abstract class domophones extends backend
        {
            /**
             * @return false|array
             */
            abstract public function getDomophones();
        }
    }

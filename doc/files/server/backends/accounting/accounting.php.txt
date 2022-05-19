<?php

    /**
     * backends accounting namespace
     */

    namespace backends\accounting {

        use backends\backend;

        /**
         * base accounting class
         */

        abstract class accounting extends backend {

            /**
             * @param object $params all params passed to api handlers
             * @param integer $code return code
             * @return void
             */

            public abstract function log($params, $code);
        }
    }


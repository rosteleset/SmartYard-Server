<?php

    /**
     * backends geocoder namespace
     */

    namespace backends\geocoder {

        use backends\backend;

        /**
         * base geocoder class
         */

        abstract class geocoder extends backend {

            /**
             * search for geo objects
             *
             * @param $search
             * @return array
             */

            public abstract function suggestions($search);
        }
    }
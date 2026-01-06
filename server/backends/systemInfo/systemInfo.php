<?php

    /**
     * backends systemInfo namespace
     */

    namespace backends\systemInfo {

        use backends\backend;

        /**
         * base systemInfo class
         */

        abstract class systemInfo extends backend {

            /**
             * @return array
             */

            abstract public function systemInfo();
        }
    }
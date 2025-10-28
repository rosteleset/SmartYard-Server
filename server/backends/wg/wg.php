<?php

    /**
    * backends wg namespace
    */

    namespace backends\wg {

        use backends\backend;

        /**
         * base wg class
         */

        abstract class wg extends backend {

            /**
             * Get WG config for RBT user
             *
             * @param string $login
             * @param string $group
             * @return string
             */

            abstract public function clientConfig($login, $group);
        }
    }

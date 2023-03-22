<?php

    /**
     * backends mqtt namespace
     */

    namespace backends\mqtt {

        use backends\backend;

        /**
         * base mqtt class
         */

        abstract class mqtt extends backend {
            /**
             * @return mixed
             */
            public function getConfig()
            {
                $cfg = $this->config["backends"]["mqtt"];
                unset($cfg["backend"]);
                return $cfg;
            }
        }
    }
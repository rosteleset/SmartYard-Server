<?php

    /**
     * backends monitoring namespace.
     */

    namespace backends\monitoring;

    use backends\backend;

    /**
     * base monitoring class.
     */
    abstract class monitoring extends backend
    {
        /**
         * @param string $deviceType
         * @param string $ip
         * @return bool|mixed
         */
        abstract public function deviceStatus($deviceType, $ip);

        /**
         * @return mixed
         */
        abstract public function configureZbx();
    }

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
         * @param int    $deviceId
         *
         * @return bool|mixed
         */
        abstract public function deviceStatus($deviceType, $deviceId);
    }

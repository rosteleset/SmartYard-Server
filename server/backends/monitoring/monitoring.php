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
         * @param array{
         *     hostIdgit : int|string,
         *     enabled: bool,
         *     ip: string,
         *     url?: string,
         *     dvrStream?: string
         * } $host
         * @return bool|mixed
         */
        abstract public function deviceStatus($deviceType, $host);

        /**
         * @param string $deviceType
         * @param array $hosts
         * @return mixed
         */
        abstract public function devicesStatus($deviceType, $hosts);

        /**
         * @return mixed
         */
        abstract public function configureMonitoring();
    }

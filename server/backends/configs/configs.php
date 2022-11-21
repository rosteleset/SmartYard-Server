<?php

    /**
     * backends configs namespace
     */

    namespace backends\configs {

        use backends\backend;

        /**
         * base configs class
         */

        abstract class configs extends backend {
            /**
             * @return mixed
             */
            abstract public function getDomophonesModels();

            /**
             * @return false|array
             */
            abstract public function getCamerasModels();

            /**
             * @return mixed
             */
            abstract public function getAsteriskServers();

            /**
             * @return mixed
             */
            abstract public function getNimbleServers();

            /**
             * @return mixed
             */
            public function getAsteriskServer($ip) {
                $asterisks = $this->getAsteriskServers();

                foreach ($asterisks as $server) {
                    if (in_array($ip, $server)) {
                        return $server;
                    }
                }

                return false;
            }

            /**
             * @return mixed
             */
            abstract public function getFRSServers();

            public function getFRSServer($ip) {
                $frss = $this->getFRSServers();

                foreach ($frss as $server) {
                    if (in_array($ip, $server)) {
                        return $server;
                    }
                }

                return false;
            }

            /**
             * @return false|array
             */
            abstract public function getCMSes();
        }
    }
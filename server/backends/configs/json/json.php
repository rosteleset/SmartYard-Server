<?php

    /**
     * backends configs namespace
     */

    namespace backends\configs {

        /**
         * config.json configs class
         */

        class json extends configs {
            /**
             * @inheritDoc
             */
            public function getDomophonesModels()
            {
                // TODO: Implement getDomophonesModels() method.
            }

            /**
             * @inheritDoc
             */
            public function getCamerasModels()
            {
                // TODO: Implement getCamerasModels() method.
            }

            /**
             * @inheritDoc
             */
            public function getAsteriskServers()
            {
                // TODO: Implement getAsteriskServers() method.
            }

            /**
             * @inheritDoc
             */
            public function getFRSServers()
            {
                return $this->config["frs_servers"];
            }
        }
    }

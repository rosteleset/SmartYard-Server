<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        /**
         * mongo mkb class
         */

        class mongo extends mkb {

            /**
             * @inheritDoc
             */

            public function getDecks() {
                return true;
            }
        }
    }

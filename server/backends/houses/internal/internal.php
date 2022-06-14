<?php

    /**
     * backends houses namespace
     */

    namespace backends\houses {

        /**
         * internal.db houses class
         */

        class internal extends houses {
            /**
             * @inheritDoc
             */
            function getHouse($houseId)
            {
                return [
                    "flats" => [],
                    "entrances" => [],
                ];
            }

            /**
             * @inheritDoc
             */
            function modifyHouse($houseId)
            {
                // TODO: Implement modifyHouse() method.
            }
        }
    }

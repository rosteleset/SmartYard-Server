<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {

        /**
         * clickhouse archive class
         */
        class clickhouse extends plog
        {
            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                if ($part == '5min') {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

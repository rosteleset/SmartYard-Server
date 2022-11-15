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
                    $this->db->modify("delete from plog_door_open where expire < " . time());
                    $this->db->modify("delete from plog_call_done where expire < " . time());
                } else {
                    return true;
                }
            }
        }
    }

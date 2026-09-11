<?php

    /**
     * backends statistics namespace
     */

    namespace backends\statistics {

        /**
         * internal statistics class
         */

        class internal extends statistics {

            /**
             * @inheritDoc
             */

            public function statistics() {
                $subscribers = (int)$this->db->get("select count(*) as c from houses_subscribers_mobile", false, [
                    "c" => "c",
                ], [
                    "fieldlify",
                ]);

                $flats = (int)$this->db->get("
                    select count(*) as c from houses_flats
                    where coalesce(manual_block, 0) = 0
                      and coalesce(auto_block, 0) = 0
                      and coalesce(admin_block, 0) = 0
                ", false, [
                    "c" => "c",
                ], [
                    "fieldlify",
                ]);

                $blockedFlats = (int)$this->db->get("
                    select count(*) as c from houses_flats
                    where coalesce(manual_block, 0) <> 0
                       or coalesce(auto_block, 0) <> 0
                       or coalesce(admin_block, 0) <> 0
                ", false, [
                    "c" => "c",
                ], [
                    "fieldlify",
                ]);

                $domophones = (int)$this->db->get("select count(*) as c from houses_domophones", false, [
                    "c" => "c",
                ], [
                    "fieldlify",
                ]);

                $cameras = (int)$this->db->get("select count(*) as c from cameras", false, [
                    "c" => "c",
                ], [
                    "fieldlify",
                ]);

                return [
                    "subscribers" => $subscribers,
                    "flats" => $flats,
                    "blockedFlats" => $blockedFlats,
                    "domophones" => $domophones,
                    "cameras" => $cameras,
                ];
            }
        }
    }

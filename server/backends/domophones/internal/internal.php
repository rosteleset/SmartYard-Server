<?php

    /**
     * backends domophones namespace
     */

    namespace backends\domophones
    {

        /**
         * internal.db domophones class
         */
        class internal extends domophones
        {
            /**
             * @inheritDoc
             */
            public function getDomophones()
            {
                return $this->db->get("select * from domophones order by domophone_id", false, [
                    "domophone_id" => "domophoneId",
                    "enabled" => "enabled",
                    "model" => "model",
                    "cms" => "cms",
                    "ip" => "ip",
                    "port" => "port",
                    "credentials" => "credentials",
                    "caller_id" => "callerId",
                    "comment" => "comment",
                    "locks_disabled" => "locksDisabled",
                    "cms_levels" => "cmsLevels"
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDomophone($enabled, $model, $cms, $ip, $port,  $credentials, $callerId, $comment, $locksDisabled, $cmsLevels)
            {
                // TODO: Implement addDomophone() method.
            }

            /**
             * @inheritDoc
             */
            public function modifyDomophone($domophoneId, $enabled, $model, $cms, $ip, $port, $credentials, $callerId, $comment, $locksDisabled, $cmsLevels)
            {
                // TODO: Implement modifyDomophone() method.
            }

            /**
             * @inheritDoc
             */
            public function deleteDomophone($domophoneId)
            {
                // TODO: Implement deleteDomophone() method.
            }

            /**
             * @inheritDoc
             */
            public function getCms($domophoneId)
            {
                // TODO: Implement getCms() method.
            }

            /**
             * @inheritDoc
             */
            public function setCms($domophoneId, $cms)
            {
                // TODO: Implement setCms() method.
            }

            /**
             * @inheritDoc
             */
            public function getModels()
            {
                // TODO: Implement getModels() method.
            }

            /**
             * @inheritDoc
             */
            public function getCMSes()
            {
                // TODO: Implement getCMSes() method.
            }
        }
    }

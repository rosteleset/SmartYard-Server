<?php

    /**
    * backends domophones namespace
    */

    namespace backends\domophones
    {

        use backends\backend;

        /**
         * base domophones class
         */
        abstract class domophones extends backend
        {
            /**
             * @return false|array
             */
            abstract public function getDomophones();

            /**
             * @return mixed
             */
            public function getServers() {
                return $this->config["backends"]["domophones"]["asterisk_servers"];
            }


            /**
             * @return false|array
             */
            abstract public function getModels();

            /**
             * @return false|array
             */
            abstract public function getCMSes();

            /**
             * @param $enabled
             * @param $model
             * @param $server
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $dtmf
             * @param $comment
             * @return false|integer
             */
            abstract public function addDomophone($enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment);

            /**
             * @param $domophoneId
             * @param $enabled
             * @param $model
             * @param $server
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $dtmf
             * @param $comment
             * @return boolean
             */
            abstract public function modifyDomophone($domophoneId, $enabled, $model, $server, $ip, $port, $credentials, $callerId, $dtmf, $comment);

            /**
             * @param $domophoneId
             * @return boolean
             */
            abstract public function deleteDomophone($domophoneId);

            /**
             * @param $domophoneId
             * @return false|array
             */
            abstract public function getDomophone($domophoneId);
        }
    }

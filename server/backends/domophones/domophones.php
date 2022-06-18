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
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $comment
             * @return false|integer
             */
            abstract public function addDomophone($enabled, $model, $ip, $port, $credentials, $callerId, $comment);

            /**
             * @param $domophoneId
             * @param $enabled
             * @param $model
             * @param $ip
             * @param $port
             * @param $credentials
             * @param $callerId
             * @param $comment
             * @return boolean
             */
            abstract public function modifyDomophone($domophoneId, $enabled, $model, $ip, $port, $credentials, $callerId, $comment);

            /**
             * @param $domophoneId
             * @return boolean
             */
            abstract public function deleteDomophone($domophoneId);

            /**
             * @param $domophoneId
             * @return false|array
             */
            abstract public function getCms($domophoneId);

            /**
             * @param $domophoneId
             * @param $cms
             * @return boolean
             */
            abstract public function setCms($domophoneId, $cms);
        }
    }

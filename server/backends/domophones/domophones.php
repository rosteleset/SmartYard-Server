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
             * @param $enabled
             * @param $model
             * @param $version
             * @param $cms
             * @param $ip
             * @param $credentials
             * @param $callerId
             * @param $comments
             * @param $locksDisabled
             * @param $cmsLevels
             * @return false|integer
             */
            abstract public function addDomophone($enabled, $model, $version, $cms, $ip, $credentials, $callerId, $comments, $locksDisabled, $cmsLevels);

            /**
             * @param $domophoneId
             * @param $enabled
             * @param $model
             * @param $version
             * @param $cms
             * @param $ip
             * @param $credentials
             * @param $callerId
             * @param $comments
             * @param $locksDisabled
             * @param $cmsLevels
             * @return boolean
             */
            abstract public function modifyDomophone($domophoneId, $enabled, $model, $version, $cms, $ip, $credentials, $callerId, $comments, $locksDisabled, $cmsLevels);

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

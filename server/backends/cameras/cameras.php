<?php

/**
 * backends cameras namespace
 */

namespace backends\cameras
{

    use backends\backend;

    /**
     * base cameras class
     */
    abstract class cameras extends backend
    {
        /**
         * @return false|array
         */
        abstract public function getCameras();

        /**
         * @return false|array
         */
        abstract public function getModels();

        /**
         * @param $enabled
         * @param $model
         * @param $ip
         * @param $httpPort
         * @param $rtspPort
         * @param $credentials
         * @param $comment
         * @return false|integer
         */
        abstract public function addCamera($enabled, $model, $ip, $httpPort, $rtspPort, $credentials, $comment);

        /**
         * @param $cameraId
         * @param $enabled
         * @param $model
         * @param $ip
         * @param $httpPort
         * @param $rtspPort
         * @param $credentials
         * @param $comment
         * @return boolean
         */
        abstract public function modifyCamera($cameraId, $enabled, $model, $ip, $httpPort, $rtspPort, $credentials, $comment);

        /**
         * @param $cameraId
         * @return boolean
         */
        abstract public function deleteCamera($cameraId);
    }
}

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
         * @param $cameraId
         * @return false|array
         */
        abstract public function getCamera($cameraId);

        /**
         * @return false|array
         */
        abstract public function getModels();

        /**
         * @param $enabled
         * @param $model
         * @param $url
         * @param $stream
         * @param $credentials
         * @param $comment
         * @return false|integer
         */
        abstract public function addCamera($enabled, $model, $url, $stream, $credentials, $comment);

        /**
         * @param $cameraId
         * @param $enabled
         * @param $model
         * @param $url
         * @param $stream
         * @param $credentials
         * @param $comment
         * @return boolean
         */
        abstract public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $comment);

        /**
         * @param $cameraId
         * @return boolean
         */
        abstract public function deleteCamera($cameraId);
    }
}

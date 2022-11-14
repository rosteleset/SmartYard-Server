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
        abstract public function getCameras($by = false, $params = false);

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
         * @param $publish
         * @param $flussonic
         * @param $lat
         * @param $lon
         * @param $direction
         * @param $angle
         * @param $distance
         * @param $md_left
         * @param $md_top
         * @param $md_width
         * @param $md_height
         * @param $common
         * @param $comment
         * @return false|integer
         */
        abstract public function addCamera($enabled, $model, $url, $stream, $credentials, $publish, $flussonic, $lat, $lon, $direction, $angle, $distance, $md_left, $md_top, $md_width, $md_height, $common, $comment);

        /**
         * @param $cameraId
         * @param $enabled
         * @param $model
         * @param $url
         * @param $stream
         * @param $credentials
         * @param $publish
         * @param $flussonic
         * @param $lat
         * @param $lon
         * @param $direction
         * @param $angle
         * @param $distance
         * @param $md_left
         * @param $md_top
         * @param $md_width
         * @param $md_height
         * @param $common
         * @param $comment
         * @return boolean
         */
        abstract public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $publish, $flussonic, $lat, $lon, $direction, $angle, $distance, $md_left, $md_top, $md_width, $md_height, $common, $comment);

        /**
         * @param $cameraId
         * @return boolean
         */
        abstract public function deleteCamera($cameraId);
    }
}

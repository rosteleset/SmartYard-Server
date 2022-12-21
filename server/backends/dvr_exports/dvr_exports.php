<?php

    /**
    * backends dvr_exports namespace
    */

    namespace backends\dvr_exports
    {

        use backends\backend;

        /**
         * base dvr_exports class
         */
        abstract class dvr_exports extends backend
        {
//
/**
             * @param $cameraId
             * @param $subscriberId
             * @param $start
             * @param $finish
             * @return boolean
             */
            abstract public function addDownloadRecord($cameraId, $subscriberId, $start, $finish);

            /**
             * @param $cameraId
             * @param $subscriberId
             * @param $start
             * @param $finish
             * @return id|false
             */
            abstract public function checkDownloadRecord($cameraId, $subscriberId, $start, $finish);

            /**
             * @param $recordId
             * @return oid|false file id
             */
            abstract public function runDownloadRecordTask($recordId);

        }
    }

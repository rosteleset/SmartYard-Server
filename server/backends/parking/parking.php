<?php

    /**
     * backends parking namespace
     */

    namespace backends\parking {

        use backends\backend;

        require_once __DIR__ . "/geometry.php";

        /**
         * base parking class: parking configurations of cameras
         */

        abstract class parking extends backend {

            /**
             * get the parking configuration of a camera
             *
             * @param int $cameraId
             * @return array|null|false configuration, null when there is no configuration, false on error
             */

            abstract public function getConfig($cameraId);

            /**
             * create or replace the parking configuration of a camera, a new revision is stored on each save
             *
             * @param int $cameraId
             * @param array $config configuration in the API format (see geometry::analyze()) with the revision it is based on
             * @return array|false saved configuration or false on error (see lastFailure())
             */

            abstract public function saveConfig($cameraId, $config);

            /**
             * delete the parking configuration of a camera with all its revisions
             *
             * @param int $cameraId
             * @return boolean
             */

            abstract public function deleteConfig($cameraId);

            /**
             * reason of the last failed operation: notFound, parkingRevisionConflict, parkingInvalidConfig or unknown
             *
             * @return string|false
             */

            abstract public function lastFailure();
        }
    }

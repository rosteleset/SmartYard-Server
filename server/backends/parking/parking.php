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

            const DEFAULT_INTERVAL = 60;
            const DEFAULT_MIN_INTERVAL = 10;

            /**
             * processing settings of the occupancy service from the backend config
             *
             * @return array [ "interval" => int, "minInterval" => int ] seconds: global processing interval and the least allowed override
             */

            public function settings() {
                $c = @$this->config["backends"]["parking"];

                $min = (is_int(@$c["minInterval"]) && $c["minInterval"] >= 1) ? $c["minInterval"] : self::DEFAULT_MIN_INTERVAL;
                $interval = (is_int(@$c["interval"]) && $c["interval"] >= 1) ? $c["interval"] : self::DEFAULT_INTERVAL;

                return [
                    "interval" => max($interval, $min),
                    "minInterval" => $min,
                ];
            }

            /**
             * options of geometry::analyze() that come from the config
             *
             * @return array
             */

            protected function analyzeOptions() {
                return [ "minInterval" => $this->settings()["minInterval"] ];
            }

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
             * cameras the occupancy service has to process: their configuration is ready and enabled
             *
             * @return array|false [ [ "cameraId" => int, "revision" => int, "interval" => int effective processing interval, seconds ] ]
             */

            abstract public function camerasToProcess();

            /**
             * snapshot of the current revision of a camera with its revision number
             *
             * @param int $cameraId
             * @return array|null|false snapshot + [ "revision" => int, "ready" => bool ], null without a configuration
             */

            abstract public function currentRevision($cameraId);

            /**
             * store the occupancy result of a processed frame, replacing the previous one and removing its frame,
             * a result computed with a revision that is not current is discarded
             *
             * @param int $cameraId
             * @param array $result [ "revision" => int, "frameWidth" => int, "frameHeight" => int, "freeSpaces" => [ { parkingAreaId, spaces: [ [ { x, y } x 4 ] ] } ] ]
             * @param string $frame JPEG of the processed frame
             * @return string|false "accepted", "discarded" or false on error (see lastFailure())
             */

            abstract public function saveResult($cameraId, $result, $frame);

            /**
             * latest occupancy result of a camera
             *
             * @param int $cameraId
             * @param bool $withFrame include the frame (base64 JPEG, null when it is gone)
             * @return array|null|false [ "revision", "processedAt", "frameWidth", "frameHeight", "freeSpaces", "frame"? ], null when there is none
             */

            abstract public function getResult($cameraId, $withFrame = false);

            /**
             * record a failed processing attempt of a camera; a successful one is recorded by saveResult()
             *
             * @param int $cameraId
             * @param string $status cameraUnavailable, detectionFailed or stale
             * @param string $reason details
             * @param int $revision revision of the attempt
             * @return boolean false on error (see lastFailure())
             */

            abstract public function saveStatus($cameraId, $status, $reason, $revision);

            /**
             * processing status of the latest attempt of a camera: notProcessed (reason disabled or notReady, time of the
             * configuration change) when the configuration is not processed at all, otherwise the stored one
             *
             * @param int $cameraId
             * @return array|null|false [ "status", "reason", "time", "revision" ], null without a configuration or before the first attempt
             */

            abstract public function getStatus($cameraId);

            /**
             * reason of the last failed operation: notFound, parkingRevisionConflict, parkingInvalidConfig, parkingInvalidResult, parkingInvalidStatus or unknown
             *
             * @return string|false
             */

            abstract public function lastFailure();
        }
    }

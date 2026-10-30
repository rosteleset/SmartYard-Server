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

            // how long the server waits for the occupancy service to process a camera on request, seconds
            const SERVICE_TIMEOUT = 60;

            // image types of camera frames the parking works with: those FALPRS and browsers both decode
            const FRAME_TYPES = [ IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_BMP => "bmp", IMAGETYPE_WEBP => "webp" ];

            /**
             * type of a camera frame by its content
             *
             * @param mixed $frame image
             * @return array|null [ "mime" => string, "extension" => string ], null when it is not a JPEG, PNG, BMP or WebP image
             */

            public static function frameType($frame): ?array {
                $info = (is_string($frame) && $frame !== "") ? @getimagesizefromstring($frame) : false;

                if (!$info || !isset(self::FRAME_TYPES[$info[2]])) {
                    return null;
                }

                return [ "mime" => image_type_to_mime_type($info[2]), "extension" => self::FRAME_TYPES[$info[2]] ];
            }

            /**
             * processing settings of the occupancy service from the backend config
             *
             * @return array [ "interval" => int, "minInterval" => int, "processNow" => bool ] seconds: global processing interval and the least
             * allowed override; processNow - the occupancy service can be asked to process a camera right now (processNow())
             */

            public function settings() {
                $c = @$this->config["backends"]["parking"];

                $min = (is_int(@$c["minInterval"]) && $c["minInterval"] >= 1) ? $c["minInterval"] : self::DEFAULT_MIN_INTERVAL;
                $interval = (is_int(@$c["interval"]) && $c["interval"] >= 1) ? $c["interval"] : self::DEFAULT_INTERVAL;

                return [
                    "interval" => max($interval, $min),
                    "minInterval" => $min,
                    "processNow" => $this->serviceUrl() !== false,
                ];
            }

            /**
             * URL of the occupancy service from the backend config ("service"), without a trailing slash
             *
             * @return string|false false when it is not set
             */

            protected function serviceUrl() {
                $url = @$this->config["backends"]["parking"]["service"];

                return (is_string($url) && trim($url) !== "") ? rtrim(trim($url), "/") : false;
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
             * @param array $result [ "revision" => int, "frameWidth" => int, "frameHeight" => int, "freeSpaces" => [ { parkingAreaId, spaces: [ [ { x, y } x 4.. ] ] } ] ], a free space is its four
             * corners or its outline on the frame when there is a lens distortion
             * @param string $frame image of the processed frame: JPEG, PNG, BMP or WebP (frameType())
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
             * parking cameras among the given cameras for the mobile app: the configuration is processed (enabled and the current
             * revision is ready) and the latest occupancy result still has its frame; totals are taken from the revision of the result
             *
             * @param int[] $cameraIds
             * @return array|false [ cameraId => [ "capacity" => int, "free" => int, "processedAt" => "Y-m-d H:i:s" ] ], cameras
             * that are not parking cameras are left out
             */

            abstract public function parkingCameras($cameraIds);

            /**
             * a parking camera (see parkingCameras()) with what the mobile app needs to draw its latest occupancy result, all in
             * pixels of the processed frame
             *
             * @param int $cameraId
             * @return array|null|false parkingCameras() summary + [ "frameWidth", "frameHeight", "frameId" (files id of the frame),
             * "areas" => [ [ "name", "capacity", "points" => [ { x, y } x 4.. ], "freeSpaces" => [ [ { x, y } x 4.. ] ] ] ] ]
             * with the enabled areas of the revision of the result in its order, null when the camera is not a parking camera;
             * outlines are the four corners or, when there is a lens distortion, the curves the edges make on the frame
             */

            abstract public function parkingCamera($cameraId);

            /**
             * parking camera (see parkingCameras()) whose latest occupancy result has the given frame
             *
             * @param string $frameId files id of the frame (parkingCamera() "frameId")
             * @return int|null|false camera id, null when the frame is not the frame of the latest result of a parking camera
             */

            abstract public function parkingFrameCamera($frameId);

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
             * processing status of the latest attempt of a camera (ok, cameraUnavailable, detectionFailed, stale, or saveFailed when
             * the server couldn't store a result), notProcessed (reason disabled or notReady, time of the
             * configuration change) when the configuration is not processed at all, otherwise the stored one
             *
             * @param int $cameraId
             * @return array|null|false [ "status", "reason", "time", "revision" ], null without a configuration or before the first attempt
             */

            abstract public function getStatus($cameraId);

            /**
             * ask the occupancy service to process a camera right now and wait for the outcome; the result and the status are
             * stored by the service as usual
             *
             * @param int $cameraId
             * @return array|null|false [ "status", "reason" ] outcome of the cycle: ok, cameraUnavailable, detectionFailed, stale,
             * skipped, discarded, noRevision or failed; null when the service doesn't process the camera; false on error
             * (lastFailure() parkingServiceUnavailable when the service is not configured or doesn't answer)
             */

            abstract public function processNow($cameraId);

            /**
             * reason of the last failed operation: notFound, parkingRevisionConflict, parkingInvalidConfig, parkingInvalidResult, parkingInvalidStatus,
             * parkingServiceUnavailable or unknown
             *
             * @return string|false
             */

            abstract public function lastFailure();
        }
    }

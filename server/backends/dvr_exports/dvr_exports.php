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

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["dvr"]) {
                    $usage["dvr"] = [];
                }

                $usage["dvr"]["run-record-download"] = [
                    "value" => "string",
                    "placeholder" => "record_id",
                    "description" => "Download record from media server",
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists("--run-record-download", $args)) {
                    $recordId = (int)$args["--run-record-download"];
                    $dvr_exports = $this;
                    if ($dvr_exports && ($uuid = $dvr_exports->runDownloadRecordTask($recordId))) {
                        $inbox = loadBackend("inbox");
                        $files = loadBackend("files");

                        $metadata = $files->getFileMetadata($uuid);

                        $msgId = $inbox->sendMessage($metadata['subscriberId'], i18n("dvr.videoReady"), i18n("dvr.threeDays", $this->config['api']['mobile'], $uuid));
                    }
                    exit(0);
                }

                parent::cli($args);
            }

        }
    }

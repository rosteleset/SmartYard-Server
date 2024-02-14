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
            public function capabilities()
            {
                return [
                    "cli" => true,
                ];
            }

            /**
             * @inheritDoc
             */
            public function cli($args)
            {
                function cliUsage()
                {
                    global $argv;
            
                    echo formatUsage("usage: {$argv[0]} dvr_exports
                    
                        dvr:
                            [--run-record-download=<record_id>]
                    ");
            
                    exit(1);
                }

                if (count($args) == 1 && array_key_exists("--run-record-download", $args) && isset($args["--run-record-download"])) {
                    $recordId = (int)$args["--run-record-download"];
                    $dvr_exports = $this;
                    if ($dvr_exports && ($uuid = $dvr_exports->runDownloadRecordTask($recordId))) {
                        $inbox = loadBackend("inbox");
                        $files = loadBackend("files");
            
                        $metadata = $files->getFileMetadata($uuid);
            
                        $msgId = $inbox->sendMessage($metadata['subscriberId'], i18n("dvr.videoReady"), i18n("dvr.threeDays", $this->$config['api']['mobile'], $uuid));
                    }
                    exit(0);
                }

                cliUsage();

                return true;
            }

        }
    }

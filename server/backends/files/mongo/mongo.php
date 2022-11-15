<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        /**
         * gridFS storage
         */

        class mongo extends files {
            private $mongo;

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis)
            {
                require_once __DIR__ . "/../../../mzfc/mongodb/autoload.php";

                parent::__construct($config, $db, $redis);

                if (@$config["backends"]["files"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["backends"]["files"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }
            }

            /**
             * @inheritDoc
             */
            public function addFile($realFileName, $fileContent, $meta = [])
            {
                return GUIDv4();
            }

            /**
             * @inheritDoc
             */
            public function getFile($uuid)
            {
                return false;
            }

            /**
             * @inheritDoc
             */
            public function deleteFile($uuid)
            {
                return true;
            }
        }
    }

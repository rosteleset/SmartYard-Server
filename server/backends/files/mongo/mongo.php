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
                require_once __DIR__ . "/../../../mzfc/mongodb/mongodb.php";

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
                $bucket = $this->mongo->rbt->selectGridFSBucket();

                $stream = $bucket->openUploadStream($realFileName);
                fwrite($stream, $fileContent);
                $id = $bucket->getFileIdForStream($stream);
                fclose($stream);

                $fileId = new \MongoDB\BSON\ObjectId($id);

                $fsFiles = "fs.files";
                $collection = $this->mongo->rbt->$fsFiles;

                $collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "metadata" => $meta ] ]);

                return $id;
            }

            /**
             * @inheritDoc
             */
            public function getFile($uuid)
            {
                $bucket = $this->mongo->rbt->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $stream = $bucket->openDownloadStream($fileId);

                return [
                    "meta" => $bucket->getFileDocumentForStream($stream),
                    "contents" => stream_get_contents($stream),
                ];
            }

            /**
             * @inheritDoc
             */
            public function getContents($uuid)
            {
                $bucket = $this->mongo->rbt->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return stream_get_contents($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function getMeta($uuid)
            {
                $bucket = $this->mongo->rbt->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return $bucket->getFileDocumentForStream($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function deleteFile($uuid)
            {
                $bucket = $this->mongo->rbt->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $bucket->delete($fileId);
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                if ($part == '5min') {
                    $fsFiles = "fs.files";

                    $cursor = $this->mongo->rbt->$fsFiles->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                    foreach ($cursor as $document) {
                        $this->deleteFile($document->_id);
                    }
                }

                return true;
            }

        }
    }

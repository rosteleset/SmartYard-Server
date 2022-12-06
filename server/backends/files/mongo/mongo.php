<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        /**
         * gridFS storage
         */

        class mongo extends files {
            private $mongo, $collection;

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis)
            {
                require_once __DIR__ . "/../../../mzfc/mongodb/mongodb.php";

                parent::__construct($config, $db, $redis);

                $this->collection = @$config["backends"]["files"]["collection"]?:"rbt";

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
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $stream = $bucket->openUploadStream($realFileName);
                fwrite($stream, $fileContent);
                $id = $bucket->getFileIdForStream($stream);
                fclose($stream);

                $fileId = new \MongoDB\BSON\ObjectId($id);

                $fsFiles = "fs.files";
                $_collection = $this->mongo->$collection->$fsFiles;

                $_collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "metadata" => $meta ] ]);

                return $id;
            }

            /**
             * @inheritDoc
             */
            public function addFileByStream($realFileName, $stream, $meta = [])
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = $bucket->uploadFromStream($realFileName, $stream);

                $fsFiles = "fs.files";
                $_collection = $this->mongo->$collection->$fsFiles;

                $_collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "metadata" => $meta ] ]);

                return $fileId;
            }

            /**
             * @inheritDoc
             */
            public function getFile($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

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
            public function getFileStream($uuid)
            {
                // TODO: Implement getFileStream() method.
            }

            /**
             * @inheritDoc
             */
            public function getContents($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return stream_get_contents($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function getMeta($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return $bucket->getFileDocumentForStream($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function deleteFile($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $bucket->delete($fileId);
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                $collection = $this->collection;

                if ($part == '5min') {
                    $fsFiles = "fs.files";

                    $cursor = $this->mongo->$collection->$fsFiles->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                    foreach ($cursor as $document) {
                        $this->deleteFile($document->_id);
                    }
                }

                return true;
            }
        }
    }

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
            public function addFile($realFileName, $fileContent, $metadata = [])
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

                $_collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "metadata" => $metadata ] ]);

                return $id;
            }

            /**
             * @inheritDoc
             */
            public function addFileByStream($realFileName, $stream, $metadata = [])
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = $bucket->uploadFromStream($realFileName, $stream);

                $fsFiles = "fs.files";
                $_collection = $this->mongo->$collection->$fsFiles;

                $_collection->updateOne([ "_id" => $fileId ], [ '$set' => [ "metadata" => $metadata ] ]);

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
                    "fileInfo" => $bucket->getFileDocumentForStream($stream),
                    "contents" => stream_get_contents($stream),
                ];
            }

            /**
             * @inheritDoc
             */
            public function getFileStream($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $stream = $bucket->openDownloadStream($fileId);

                return [
                    "fileInfo" => $bucket->getFileDocumentForStream($stream),
                    "stream" => $stream,
                ];
            }

            /**
             * @inheritDoc
             */
            public function getFileContents($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return stream_get_contents($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function getFileInfo($uuid)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                return $bucket->getFileDocumentForStream($bucket->openDownloadStream($fileId));
            }

            /**
             * @inheritDoc
             */
            public function setFileMetadata($uuid, $metadata)
            {
                $fsFiles = "fs.files";
                $collection = $this->collection;

                return $this->mongo->$collection->$fsFiles->updateOne([ "_id" => new \MongoDB\BSON\ObjectId($uuid) ], [ '$set' => [ "metadata" => $meta ]]);
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
            public function uuidToGUIDv4($uuid)
            {
                $uuid = "10001000" . $uuid;

                $hyphen = chr(45);
                return substr($uuid,  0,  8) . $hyphen . substr($uuid,  8,  4) . $hyphen . substr($uuid, 12,  4) . $hyphen . substr($uuid, 16,  4) . $hyphen . substr($uuid, 20, 12);
            }

            /**
             * @inheritDoc
             */
            public function GUIDv4ToUuid($guidv4)
            {
                return str_replace("-", "", substr($guidv4, 8));
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

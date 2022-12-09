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
            public function addFileByContents($realFileName, $fileContents, $metadata = [])
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $stream = $bucket->openUploadStream($realFileName);
                fwrite($stream, $fileContents);
                $id = $bucket->getFileIdForStream($stream);
                fclose($stream);

                if ($metadata) {
                    $this->setFileMetadata($id, $metadata);
                }

                return (string)$id;
            }

            /**
             * @inheritDoc
             */
            public function addFileByStream($realFileName, $stream, $metadata = [])
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $id = $bucket->uploadFromStream($realFileName, $stream);

                if ($metadata) {
                    $this->setFileMetadata($id, $metadata);
                }

                return (string)$id;
            }

            /**
             * @inheritDoc
             */
            public function getFile($uuid, $stream = false)
            {
                $collection = $this->collection;

                $bucket = $this->mongo->$collection->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $stream = $bucket->openDownloadStream($fileId);

                if ($stream) {
                    return [
                        "fileInfo" => $bucket->getFileDocumentForStream($stream),
                        "stream" => $stream,
                    ];
                } else {
                    return [
                        "fileInfo" => $bucket->getFileDocumentForStream($stream),
                        "contents" => stream_get_contents($stream),
                    ];
                }
            }

            /**
             * @inheritDoc
             */
            public function getFileContents($uuid)
            {
                return $this->getFile($uuid)["contents"];
            }

            /**
             * @inheritDoc
             */
            public function getFileStream($uuid)
            {
                return $this->getFile($uuid, true)["stream"];
            }

            /**
             * @inheritDoc
             */
            public function getFileInfo($uuid)
            {
                return $this->getFile($uuid, true)["fileInfo"];
            }

            /**
             * @inheritDoc
             */
            public function setFileMetadata($uuid, $metadata)
            {
                $fsFiles = "fs.files";
                $collection = $this->collection;

                return $this->mongo->$collection->$fsFiles->updateOne([ "_id" => new \MongoDB\BSON\ObjectId($uuid) ], [ '$set' => [ "metadata" => $metadata ]]);
            }

            /**
             * @inheritDoc
             */
            public function getFileMetadata($uuid)
            {
                return $this->getFileInfo($uuid)->metadata;
            }

            /**
             * @inheritDoc
             */
            public function searchFilesBy($metadataField, $fieldValue)
            {
                $collection = $this->collection;

                $fsFiles = "fs.files";

                $cursor = $this->mongo->$collection->$fsFiles->find([ "metadata.$metadataField" => [ '$eq' => $fieldValue ] ]);

                $files = [];

                foreach ($cursor as $document) {
                    $files[] = $document;
                }

                return $files;
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
            public function ToGUIDv4($uuid)
            {
                $uuid = "10001000" . $uuid;

                $hyphen = chr(45);
                return substr($uuid,  0,  8) . $hyphen . substr($uuid,  8,  4) . $hyphen . substr($uuid, 12,  4) . $hyphen . substr($uuid, 16,  4) . $hyphen . substr($uuid, 20, 12);
            }

            /**
             * @inheritDoc
             */
            public function fromGUIDv4($guidv4)
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

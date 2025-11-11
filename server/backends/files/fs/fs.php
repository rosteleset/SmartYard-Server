<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        /**
         * filesystem storage
         */

        class fs extends files {

            private $mongo, $dbName;

            /**
             * @inheritDoc
             */

            public function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                $this->dbName = @$config["backends"]["files"]["db"] ?: "rbt";

                if (@$config["mongo"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["mongo"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }
            }

            /**
             * @inheritDoc
             */

            public function addFile($realFileName, $stream, $metadata = []) {
                $db = $this->dbName;

                $bucket = $this->mongo->$db->selectGridFSBucket();

                $id = $bucket->uploadFromStream($realFileName, $stream);

                if ($metadata) {
                    $this->setFileMetadata($id, $metadata);
                }

                return (string)$id;
            }

            /**
             * @inheritDoc
             */

            public function getFile($uuid) {
                $db = $this->dbName;

                $bucket = $this->mongo->$db->selectGridFSBucket();

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

            public function getFileStream($uuid) {
                return $this->getFile($uuid)["stream"];
            }

            /**
             * @inheritDoc
             */

            public function getFileInfo($uuid) {
                return $this->getFile($uuid)["fileInfo"];
            }

            /**
             * @inheritDoc
             */

            public function setFileMetadata($uuid, $metadata) {
                $collection = "fs.files";
                $db = $this->dbName;

                return $this->mongo->$db->$collection->updateOne([ "_id" => new \MongoDB\BSON\ObjectId($uuid) ], [ '$set' => [ "metadata" => $metadata ]]);
            }

            /**
             * @inheritDoc
             */

            public function getFileMetadata($uuid) {
                return $this->getFileInfo($uuid)->metadata;
            }

            /**
             * @inheritDoc
             */

            public function searchFiles($query, $skip = 0, $limit = 1024) {
                $collection = "fs.files";
                $db = $this->dbName;

                $cursor = $this->mongo->$db->$collection->find($query, [
                    "sort" => [
                        "filename" => 1,
                    ],
                ]);

                $files = [];
                foreach ($cursor as $document) {
                    $document = object_to_array($document);
                    $document["id"] = (string)$document["_id"]["oid"];
                    unset($document["_id"]);
                    $files[] = $document;
                }

                return $files;
            }

            /**
             * @inheritDoc
             */

            public function deleteFile($uuid) {
                $db = $this->dbName;

                $bucket = $this->mongo->$db->selectGridFSBucket();

                if ($bucket) {
                    try {
                        $bucket->delete(new \MongoDB\BSON\ObjectId($uuid));
                        return true;
                    } catch (\Exception $e) {
                        setLastError($e->getMessage());
                    }
                }

                return false;
            }

            /**
             * @inheritDoc
             */

            public function deleteFiles($query) {
                $files = $this->searchFiles($query);

                foreach ($files as $f) {
                    if (!$this->deleteFile($f["id"])) {
                        return false;
                    }
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                $collection = "fs.files";
                $db = $this->dbName;

                if ($part == '5min') {

                    $cursor = $this->mongo->$db->$collection->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                    foreach ($cursor as $document) {
                        $this->deleteFile($document->_id);
                    }
                }

                return true;
            }
        }
    }

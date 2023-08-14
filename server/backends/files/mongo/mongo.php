<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

    use Exception;
    use logger\Logger;

        /**
         * gridFS storage
         */

        class mongo extends files {

            private $mongo, $dbName;

            /**
             * @inheritDoc
             */
            public function __construct($config, $db, $redis, $login = false)
            {
                require_once __DIR__ . "/../../../mzfc/mongodb/vendor/autoload.php";

                parent::__construct($config, $db, $redis, $login);

                $this->dbName = @$config["backends"]["files"]["db"]?:"rbt";

                if (@$config["backends"]["files"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["backends"]["files"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }
            }

            /**
             * @inheritDoc
             */
            public function addFile($realFileName, $stream, $metadata = [])
            {
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
            public function getFile($uuid)
            {
                $db = $this->dbName;

                $bucket = $this->mongo->$db->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $stream = $bucket->openDownloadStream($fileId);

                return [
                    "fileInfo" => $bucket->getFileDocumentForStream($stream),
                    "stream" => $stream,
                ];
            }

            public function getFileBytes($uuid)
            {
                try {
                    $bucket = $this->mongo->{$this->dbName}->selectGridFSBucket();
                    $image = $bucket->findOne(["_id" => new \MongoDB\BSON\ObjectId($uuid)]);
    
                    if ($image)
                        return $image->getBytes();
                    else Logger::channel('mongo')->debug('File not found', ['data' => $uuid]);
                } catch(Exception $e) {
                    Logger::channel('mongo')->error('Error get file bytes'.PHP_EOL.$e);
                }

                return null;
            }

            /**
             * @inheritDoc
             */
            public function getFileStream($uuid)
            {
                return $this->getFile($uuid)["stream"];
            }

            /**
             * @inheritDoc
             */
            public function getFileInfo($uuid)
            {
                return $this->getFile($uuid)["fileInfo"];
            }

            /**
             * @inheritDoc
             */
            public function setFileMetadata($uuid, $metadata)
            {
                $collection = "fs.files";
                $db = $this->dbName;

                return $this->mongo->$db->$collection->updateOne([ "_id" => new \MongoDB\BSON\ObjectId($uuid) ], [ '$set' => [ "metadata" => $metadata ]]);
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
            public function searchFiles($query)
            {
                $collection = "fs.files";
                $db = $this->dbName;

                $cursor = $this->mongo->$db->$collection->find($query, [
                    "sort" => [
                        "filename" => 1,
                    ],
                ]);

                $files = [];
                foreach ($cursor as $document) {
                    $document = json_decode(json_encode($document), true);
                    $document["id"] = (string)$document["_id"]["\$oid"];
                    unset($document["_id"]);
                    $files[] = $document;
                }

                return $files;
            }

            /**
             * @inheritDoc
             */
            public function deleteFile($uuid)
            {
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
            public function toGUIDv4($uuid)
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
                if ($part == '5min') {
                    $collection = "fs.files";
                    $db = $this->dbName;

                    $cursor = $this->mongo->$db->$collection->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                    foreach ($cursor as $document) {
                        $this->deleteFile($document->_id);
                    }
                }

                return true;
            }
        }
    }

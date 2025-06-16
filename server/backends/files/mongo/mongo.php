<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        /**
         * gridFS storage
         */

        class mongo extends files {

            private $mongo, $dbName;

            /**
             * @inheritDoc
             */

            public function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                $this->dbName = @$config["backends"]["files"]["db"]?:"rbt";

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

                $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $stream);

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

            public function searchFiles($query) {
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

            /**
             * @inheritDoc
             */

            public function cliUsage() {
                $usage = parent::cliUsage();

                if (!@$usage["indexes"]) {
                    $usage["indexes"] = [];
                }

                $usage["indexes"]["list-indexes"] = [
                    "description" => "List indexes for GridFS",
                ];

                $usage["indexes"]["create-indexes"] = [
                    "description" => "(Re)Create default GridFS indexes",
                ];

                $usage["indexes"]["drop-indexes"] = [
                    "description" => "Drop default GridFS indexes",
                ];

                $usage["indexes"]["create-index"] = [
                    "value" => "string",
                    "placeholder" => "field1[,field2...]",
                    "description" => "Manually create GridFS index",
                ];

                $usage["indexes"]["drop-index"] = [
                    "value" => "string",
                    "placeholder" => "index",
                    "description" => "Drop single GridFS index",
                ];

                return $usage;
            }

            /**
             * @inheritDoc
             */

            public function cli($args) {
                if (array_key_exists("--list-indexes", $args)) {
                    $collection = "fs.files";
                    $db = $this->dbName;

                    $c = 0;

                    $indexes = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$collection->listIndexes()));

                    foreach ($indexes as $i) {
                        echo $i["name"] . "\n";
                        $c++;
                    }

                    echo "$c indexes total\n";

                    exit(0);
                }

                if (array_key_exists("--create-indexes", $args)) {
                    $indexes = [
                        "filename",
                        "uploadDate",
                        "md5"
                    ];

                    $files = $this->searchFiles([]);
                    foreach ($files as $file) {
                        if ($file["metadata"] && is_array($file["metadata"])) {
                            foreach ($file["metadata"] as $i => $m) {
                                $indexes[] = "metadata.$i";
                            }
                        }
                    }

                    $indexes = array_unique($indexes);

                    $collection = "fs.files";
                    $db = $this->dbName;

                    $c = 0;

                    foreach ($indexes as $index) {
                        try {
                            $this->mongo->$db->$collection->createIndex([ $index => 1 ], [ "name" => "index_" . $index ]);
                            $c++;
                        } catch (\Exception $e) {
                            //
                        }
                    }

                    echo "$c indexes created\n";

                    exit(0);
                }

                if (array_key_exists("--drop-indexes", $args)) {
                    $collection = "fs.files";
                    $db = $this->dbName;

                    $indexes = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$collection->listIndexes()));

                    $c = 0;

                    foreach ($indexes as $i) {
                        if (strpos($i["name"], "index_") === 0) {
                            try {
                                $this->mongo->$db->$collection->dropIndex($i["name"]);
                                $c++;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }

                    echo "$c indexes dropped\n";

                    exit(0);
                }

                if (array_key_exists($args["--create-index"])) {
                    $collection = "fs.files";
                    $db = $this->dbName;

                    $c = 0;

                    $fields = explode(",", $args["--create-index"]);

                    $index = [];
                    $indexName = "";

                    foreach ($fields as $f) {
                        $index[$f] = 1;
                        $indexName .= "_" . $f;
                    }


                    try {
                        $this->mongo->$db->$collection->createIndex($index, [ "name" => "manual_index" . $indexName ]);
                        $c++;
                    } catch (\Exception $e) {
                        //
                    }

                    echo "$c indexes created\n";

                    exit(0);
                }

                if (array_key_exists($args["--drop-index"])) {
                    $collection = "fs.files";
                    $db = $this->dbName;

                    $c = 0;

                    $indexes = array_map(function ($indexInfo) {
                        return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                    }, iterator_to_array($this->mongo->$db->$acr->listIndexes()));

                    foreach ($indexes as $i) {
                        if ($i["name"] == $args["--drop-index"]) {
                            try {
                                $this->mongo->$db->$collection->dropIndex($i["name"]);
                                $c++;
                            } catch (\Exception $e) {
                                //
                            }
                        }
                    }

                    echo "$c indexes dropped\n";

                    exit(0);
                }

                parent::cli($args);
            }
        }
    }

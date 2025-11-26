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

                $tmpfs = loadBackend("tmpfs");

                $s = false;

                if ($tmpfs && $metadata && @$metadata["expire"]) {
                    $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $this->contentsToStream(""));
                    $s = $tmpfs->putFile($id, $stream);
                } else {
                    $extfs = loadBackend("extfs");
                    if ($extfs && $metadata && @$metadata["external"]) {
                        $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $this->contentsToStream(""));
                        $metadata["md5id"] = md5($id);
                        $s = $extfs->putFile($id, $stream);
                    } else {
                        $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $stream);
                    }
                }

                if ($s !== false) {
                    if (!$metadata) {
                        $metadata = [];
                    }
                    $metadata["realLength"] = $s;
                }

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

                $tmpfs = loadBackend("tmpfs");
                $extfs = loadBackend("extfs");

                $bucket = $this->mongo->$db->selectGridFSBucket();

                $fileId = new \MongoDB\BSON\ObjectId($uuid);

                $tstream = false;
                $estream = false;

                if ($tmpfs) {
                    $tstream = $tmpfs->getFile($uuid);
                }

                if ($extfs) {
                    $estream = $extfs->getFile($uuid);
                }

                $stream = $bucket->openDownloadStream($fileId);
                $info = $bucket->getFileDocumentForStream($stream);

                if (@$info["metadata"] && @$info["metadata"]["realLength"]) {
                    $info["length"] = $info["metadata"]["realLength"];
                }

                return [
                    "fileInfo" => $info,
                    "stream" => $tstream ?: ( $estream ?: $stream),
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

                if (@$query["_id"]) {
                    $query["_id"] = new \MongoDB\BSON\ObjectId($query["_id"]);
                }

                if (@$query["id"]) {
                    $query["_id"] = new \MongoDB\BSON\ObjectId($query["id"]);
                    unset($query["id"]);
                }

                $cursor = $this->mongo->$db->$collection->find($query, [
                    "sort" => [
                        "filename" => 1,
                    ],
                    "skip" => (int)$skip,
                    "limit" => (int)$limit,
                ]);

                $files = [];
                foreach ($cursor as $document) {
                    $document = object_to_array($document);
                    $document["id"] = (string)$document["_id"]["oid"];

                    if (@$document["metadata"] && @$document["metadata"]["realLength"]) {
                        $document["length"] = $document["metadata"]["realLength"];
                    }

                    if (@$document["metadata"] && @$document["metadata"]["realUploadDate"]) {
                        $document["uploadDate"] = $document["metadata"]["realUploadDate"];
                    }

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

                $tmpfs = loadBackend("tmpfs");

                if ($tmpfs) {
                    try {
                        $tmpfs->deleteFile($uuid);
                    } catch (\Exception $e) {
                        //
                    }
                }

                $extfs = loadBackend("extfs");

                if ($extfs) {
                    try {
                        $extfs->deleteFile($uuid);
                    } catch (\Exception $e) {
                        //
                    }
                }

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

            public function cleanup() {
                $collection = "fs.files";
                $db = $this->dbName;

                $cursor = $this->mongo->$db->$collection->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                foreach ($cursor as $document) {
                    $this->deleteFile($document->_id);
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function cron($part) {
                if ($part == "5min") {
                    $this->cleanup();
                }

                if (@$this->bconfig["autocompact"] && $part == $this->bconfig["autocompact"]) {
                    $db = $this->dbName;

                    try {
                        $cursor = $this->mongo->$db->command([ "compact" => "fs.chunks", "dryRun" => false, "force" => true ]);
                    } catch(\Exception $e) {
                        die($e->getMessage() . "\n");
                    }

                    $response = object_to_array($cursor->toArray()[0]);

                    if (!$response || !array_key_exists("bytesFreed", $response)) {
                        print_r($response);
                    }

                    try {
                        $cursor = $this->mongo->$db->command([ "compact" => "fs.files", "dryRun" => false, "force" => true ]);
                    } catch(\Exception $e) {
                        die($e->getMessage() . "\n");
                    }

                    $response = object_to_array($cursor->toArray()[0]);

                    if (!$response || !array_key_exists("bytesFreed", $response)) {
                        print_r($response);
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

                $usage["maintenance"]["cleanup"] = [
                    "description" => "Cleanup GridFS",
                ];

                if (loadBackend("extfs")) {
                    $usage["maintenance"]["move-to-extfs"] = [
                        "description" => "Move files from GridFS to extfs",
                        "params" => [
                            [
                                "query" => [
                                    "value" => "string",
                                    "placeholder" => "json-query",
                                    "optional" => true,
                                ],
                            ]
                        ],
                    ];
                }

                $usage["maintenance"]["force-expire"] = [
                    "description" => "Bulk update of fs.files.metadata.expire value",
                    "value" => "string",
                    "placeholder" => "date in strtotime format",
                    "params" => [
                        [
                            "query" => [
                                "value" => "string",
                                "placeholder" => "json-query",
                                "optional" => true,
                            ],
                        ]
                    ],
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

                    $skip = 0;
                    $step = 1024;

                    $t = [];

                    while ($files = $this->searchFiles([], $skip, $step)) {
                        $skip += $step;
                        foreach ($files as $file) {
                            if ($file["metadata"] && is_array($file["metadata"])) {
                                foreach ($file["metadata"] as $i => $m) {
                                    $t["metadata.$i"] = 1;
                                }
                            }
                        }
                    }

                    foreach ($t as $i => $one) {
                        $indexes[] = "metadata.$i";
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

                    echo "$c indexes [re]created\n";

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

                if (array_key_exists("--create-index", $args)) {
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

                if (array_key_exists("--drop-index", $args)) {
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

                if (array_key_exists("--cleanup", $args)) {
                    $this->cleanup();

                    exit(0);
                }

                if (array_key_exists("--move-to-extfs", $args)) {
                    if (loadBackend("extfs")) {

                        $filter = false;
                        if (array_key_exists("--query", $args)) {
                            $filter = json_decode($args["--query"], true);
                        }

                        if ($filter === NULL) {
                            echo "invalid filter query\n";
                            exit(1);
                        }

                        $query = [
                            '$and' => [
                                [
                                    'length' => [
                                        '$gt' => 0,
                                    ],
                                ],
                                [
                                    'metadata.expire' =>  [
                                        '$exists' => false,
                                    ],
                                ],
                                [
                                    '$or' => [
                                        [
                                            'metadata.external' => [
                                                '$exists' => false,
                                            ]
                                        ],
                                        [
                                            'metadata.external' => false
                                        ],
                                    ],
                                ],
                            ],
                        ];

                        if ($filter) {
                            $query['$and'][] = [
                                '$and' => [ $filter ],
                            ];
                        }

                        $collection = "fs.files";
                        $db = $this->dbName;

                        $c = 0;

                        do {
                            $p = 0;
                            while ($files = $this->searchFiles($query, 0, 1024)) {
                                foreach ($files as $file) {
                                    if (!@$file["metadata"]) {
                                        $file["metadata"] = [];
                                    }
                                    $file["metadata"]["external"] = true;
                                    if (@$file["uploadDate"]) {
                                        $file["metadata"]["realUploadDate"] = $file["uploadDate"];
                                    }
                                    $fd = $this->getFile($file["id"])["stream"] ;
                                    fseek($fd, 0);
                                    $this->addFile($file["filename"], $fd, $file["metadata"]);
                                    $this->deleteFile($file["id"]);
                                    echo ".";
                                    $c++;
                                    $p++;
                                }
                            }
                        } while ($p);

                        if ($c) {
                            echo "\n";
                        }

                        echo "$c file(s) moved\n";
                    } else {
                        echo "extfs is not available\n";
                    }

                    exit(0);
                }

                if (array_key_exists("--force-expire", $args)) {

                    $expire = strtotime($args["--force-expire"]);

                    if ($expire) {
                        $filter = false;
                        if (array_key_exists("--query", $args)) {
                            $filter = json_decode($args["--query"], true);
                        }

                        if ($filter === NULL) {
                            echo "invalid filter query\n";
                            exit(1);
                        }

                        $query = [
                            '$and' => [
                                [
                                    'metadata.expire' =>  [
                                        '$exists' => true,
                                    ],
                                ],
                                [
                                    'metadata.expire' => [
                                        '$ne' => (int)$expire
                                    ]
                                ],
                                [
                                    'metadata.expire' => [
                                        '$ne' => (string)$expire
                                    ]
                                ],
                            ],
                        ];

                        if ($filter) {
                            $query['$and'][] = [
                                '$and' => [ $filter ],
                            ];
                        }

                        $collection = "fs.files";
                        $db = $this->dbName;

                        $c = $this->mongo->$db->$collection->updateMany($query, [ '$set' => [ "metadata.expire" => $expire ]]);

                        echo $c->getMatchedCount() . " file(s) matched, " . $c->getModifiedCount() . " file(s) updated\n";

                        exit(0);
                    }
                }

                parent::cli($args);
            }
        }
    }

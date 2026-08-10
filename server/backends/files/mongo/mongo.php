<?php

    /**
     * backends files namespace
     */

    namespace backends\files {

        use MongoDB\{
            BSON\ObjectId,
            Client,
        };
        use RuntimeException;
        use Throwable;

        /**
         * gridFS storage
         */

        class mongo extends files {

            private const STORAGE_GRIDFS = "gridfs";
            private const STORAGE_TMPFS = "tmpfs";
            private const STORAGE_EXTFS = "extfs";

            private $mongo, $dbName;

            private function normalizeStorage($storage) {
                $storage = strtolower((string)$storage);

                if ($storage === "mongo") {
                    return self::STORAGE_GRIDFS;
                }

                if (!in_array($storage, [ self::STORAGE_GRIDFS, self::STORAGE_TMPFS, self::STORAGE_EXTFS ], true)) {
                    throw new \InvalidArgumentException("Unknown file storage: $storage");
                }

                return $storage;
            }

            private function storageForNewFile($metadata) {
                if (@$metadata["storage"]) {
                    return $this->normalizeStorage($metadata["storage"]);
                }

                if (@$metadata["external"]) {
                    if (loadBackend("extfs")) {
                        return self::STORAGE_EXTFS;
                    }
                }

                return self::STORAGE_GRIDFS;
            }

            private function storageForStoredFile($metadata) {
                if (@$metadata["storage"]) {
                    return $this->normalizeStorage($metadata["storage"]);
                }

                // Infer records written before metadata.storage was introduced.
                if (@$metadata["external"] && array_key_exists("realLength", $metadata)) {
                    return self::STORAGE_EXTFS;
                }

                if (@$metadata["expire"] && array_key_exists("realLength", $metadata)) {
                    return self::STORAGE_TMPFS;
                }

                return self::STORAGE_GRIDFS;
            }

            private function storageBackend($storage) {
                if ($storage === self::STORAGE_TMPFS || $storage === self::STORAGE_EXTFS) {
                    $backend = loadBackend($storage);
                    if (!$backend) {
                        throw new RuntimeException("File storage backend is not available: $storage");
                    }

                    return $backend;
                }

                return false;
            }

            private function objectId($uuid) {
                if ($uuid instanceof ObjectId) {
                    return $uuid;
                }

                return new ObjectId((string)$uuid);
            }

            /**
             * @inheritDoc
             */

            public function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                $this->dbName = @$config["backends"]["files"]["db"] ?: "rbt";

                if (@$config["mongo"]["uri"]) {
                    $this->mongo = new Client($config["mongo"]["uri"]);
                } else {
                    $this->mongo = new Client();
                }
            }

            /**
             * @inheritDoc
             */

            public function addFile($realFileName, $stream, $metadata = []) {
                $db = $this->dbName;

                $bucket = $this->mongo->$db->selectGridFSBucket();

                $metadata = is_array($metadata) ? $metadata : [];
                $storage = $this->storageForNewFile($metadata);
                $metadata["storage"] = $storage;

                if ($storage === self::STORAGE_GRIDFS) {
                    $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $stream);
                } else {
                    $backend = $this->storageBackend($storage);
                    $id = $bucket->uploadFromStream(preg_replace('/[\+]/', '_', $realFileName), $this->contentsToStream(""));

                    try {
                        $size = $backend->putFile($id, $stream);
                        if ($size === false) {
                            throw new RuntimeException("Failed to write file to $storage");
                        }
                    } catch (Throwable $e) {
                        try {
                            $backend->deleteFile($id);
                        } catch (Throwable) {
                            // Preserve the original storage error.
                        }
                        $bucket->delete($id);
                        throw $e;
                    }

                    $metadata["realLength"] = $size;

                    if ($storage === self::STORAGE_EXTFS) {
                        $metadata["md5id"] = md5((string)$id);
                    }
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

                $bucket = $this->mongo->$db->selectGridFSBucket();

                $fileId = $this->objectId($uuid);
                $stream = $bucket->openDownloadStream($fileId);
                $info = $bucket->getFileDocumentForStream($stream);

                $metadata = @$info["metadata"] ? object_to_array($info["metadata"]) : [];
                $storage = $this->storageForStoredFile($metadata);

                if ($storage !== self::STORAGE_GRIDFS) {
                    $stream = $this->storageBackend($storage)->getFile($uuid);
                    if (!$stream) {
                        throw new RuntimeException("File content is missing from $storage: $uuid");
                    }
                }

                if (@$info["metadata"] && isset($info["metadata"]["realLength"])) {
                    $info["length"] = $info["metadata"]["realLength"];
                }

                return [
                    "fileInfo" => $info,
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

                return $this->mongo->$db->$collection->updateOne([ "_id" => $this->objectId($uuid) ], [ '$set' => [ "metadata" => $metadata ]]);
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
                    $query["_id"] = $this->objectId($query["_id"]);
                }

                if (@$query["id"]) {
                    $query["_id"] = $this->objectId($query["id"]);
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

                    if (@$document["metadata"] && isset($document["metadata"]["realLength"])) {
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

                $bucket = $this->mongo->$db->selectGridFSBucket();

                if ($bucket) {
                    try {
                        $fileId = $this->objectId($uuid);
                        $stream = $bucket->openDownloadStream($fileId);
                        $info = $bucket->getFileDocumentForStream($stream);
                        $metadata = @$info["metadata"] ? object_to_array($info["metadata"]) : [];
                        $storage = $this->storageForStoredFile($metadata);

                        if ($storage !== self::STORAGE_GRIDFS) {
                            $this->storageBackend($storage)->deleteFile($uuid);
                        }

                        $bucket->delete($fileId);
                        return true;
                    } catch (Throwable $e) {
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

                $c = 0;

                $cursor = $this->mongo->$db->$collection->find([ "metadata.expire" => [ '$lt' => time() ] ]);
                foreach ($cursor as $document) {
                    if ($this->deleteFile($document->_id)) {
                        $c++;
                    }
                }

                return $c;
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
                    $toGiB = fn($bytes) => round($bytes / 1024 / 1024 / 1024, 2);

                    try {
                        $cursor = $this->mongo->$db->command([ "compact" => "fs.chunks", "dryRun" => false, "force" => true ]);
                    } catch(\Exception $e) {
                        throw $e;
                    }

                    $response = object_to_array($cursor->toArray()[0]);

                    if (!$response || !array_key_exists("bytesFreed", $response)) {
                        print_r($response);
                    } else {
                        echo "files [$part] fs.chunks bytesFreed: {$response["bytesFreed"]} (" . $toGiB($response["bytesFreed"]) . " GiB)\n";
                    }

                    try {
                        $cursor = $this->mongo->$db->command([ "compact" => "fs.files", "dryRun" => false, "force" => true ]);
                    } catch(\Exception $e) {
                        throw $e;
                    }

                    $response = object_to_array($cursor->toArray()[0]);

                    if (!$response || !array_key_exists("bytesFreed", $response)) {
                        print_r($response);
                    } else {
                        echo "files [$part] fs.files bytesFreed: {$response["bytesFreed"]} (" . $toGiB($response["bytesFreed"]) . " GiB)\n";
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
                    }, iterator_to_array($this->mongo->$db->$collection->listIndexes()));

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
                    $collection = "fs.files";
                    $db = $this->dbName;

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

                        $c = 0;

                        do {
                            $p = 0;
                            while ($files = $this->searchFiles($query, 0, 1024)) {
                                foreach ($files as $file) {
                                    if (!@$file["metadata"]) {
                                        $file["metadata"] = [];
                                    }
                                    $file["metadata"]["external"] = true;
                                    $file["metadata"]["storage"] = self::STORAGE_EXTFS;
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
                            // TODO (?)
                            $this->mongo->$db->command([ "compact" => "fs.chunks", "dryRun" => false, "force" => true ]);
                        } while ($p);

                        if ($c) {
                            echo "\n";
                        }

                        // TODO (?)
                        $this->mongo->$db->command([ "compact" => "fs.chunks", "dryRun" => false, "force" => true ]);

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

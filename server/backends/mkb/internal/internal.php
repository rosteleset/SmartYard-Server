<?php

    /**
     * backends mkb namespace
     */

    namespace backends\mkb {

        /**
         * internal mkb class
         */

        class internal extends mkb {

            protected $mongo, $dbName;

            /**
             * @inheritDoc
             */

            public function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                $this->dbName = @$config["backends"]["mkb"]["db"] ?: "mkb";

                if (@$config["mongo"]["uri"]) {
                    $this->mongo = new \MongoDB\Client($config["mongo"]["uri"]);
                } else {
                    $this->mongo = new \MongoDB\Client();
                }
            }

            /**
             * put json
             *
             * @param
             */

            private function put($json, $key = "_id") {
                $db = $this->dbName;
                $login = $this->login;

                if (@$json[$key]) {
                    $id = $json[$key];
                    if ($key == "_id") {
                        $_id = new \MongoDB\BSON\ObjectID($id);
                        unset($json["_id"]);
                    } else {
                        $_id = $id;
                    }
                    $this->mongo->$db->$login->replaceOne([ $key => $_id ], $json, [ "upsert" => true ]);
                } else {
                    $id = object_to_array($this->mongo->$db->$login->insertOne($json)->getInsertedId())["oid"];
                }

                return $id;
            }

            /**
             * get json
             */

            private function get($query = false, $options = false) {
                $db = $this->dbName;
                $login = $this->login;

                if (!$query) {
                    $query = [];
                }

                array_walk_recursive($query, function (&$value, $key) {
                    if ($key === '_id') {
                        $value = new \MongoDB\BSON\ObjectID($value);
                    }
                });

                if (!$options) {
                    $options = [];
                }

                $i = [];
                $jsons = $this->mongo->$db->$login->find($query, $options);
                foreach ($jsons as $json) {
                    $x = object_to_array($json);
                    $x["_id"] = $x["_id"]["oid"];
                    $i[] = $x;
                }

                return $i;
            }

            /**
             * delete json
             */

            private function delete($query) {
                $db = $this->dbName;
                $login = $this->login;

                if (!$query) {
                    $query = [];
                }

                array_walk_recursive($query, function (&$value, $key) {
                    if ($key === '_id') {
                        $value = new \MongoDB\BSON\ObjectID($value);
                    }
                });

                $this->mongo->$db->$login->deleteMany($query);

                return true;
            }

            /**
             * list indexes
             */

            private function listIndexes() {
                $db = $this->dbName;
                $login = $this->login;

                return array_map(function ($indexInfo) {
                    return [ 'v' => $indexInfo->getVersion(), 'key' => $indexInfo->getKey(), 'name' => $indexInfo->getName() ];
                }, iterator_to_array($this->mongo->$db->$login->listIndexes()));
            }

            /**
             * create indexes
             */

            private function createIndexes($collection) {
                $db = $this->dbName;

                $fullText = [
                    "subject" => "text",
                    "body" => "text",
                    "tags" => "text",
                    "comments.body" => "text",
                    "subtasks.id" => "text",
                    "subtasks.text" => "text",
                    "subtasks.value" => "text",
                ];

                $this->mongo->$db->$collection->createIndex($fullText, [ "default_language" => @$this->config["language"] ?: "en", "name" => "fullText" ]);

                $fields = [
                    "type",
                    "name",
                    "subject",
                    "color",
                    "body",
                    "desk",
                    "date",
                    "done",
                ];

                foreach ($fields as $i) {
                    $this->mongo->$db->$collection->createIndex([ $i => 1 ], [ "name" => "index_" . $i, ]);
                }

                return true;
            }

            /**
             * is collection exists
             */

            private function collectionExists($collection) {
                $db = $this->dbName;

                $exists = false;

                foreach ($this->mongo->$db->listCollections() as $collectionInfo) {
                    if ($collectionInfo->getName() === $collection) {
                        $exists = true;
                        break;
                    }
                }

                return $exists;
            }

            /**
             * @inheritDoc
             */

            public function getDesks() {
                return $desks = $this->get([ "type" => "desk" ]);
            }

            /**
             * @inheritDoc
             */

            public function upsertDesk($desk) {
                $desk["type"] = "desk";

                $exists = $this->collectionExists($this->login);

                $r = $this->put($desk);

                if (!$exists) {
                    $this->createIndexes($this->login);
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function deleteDesk($name) {
                return $this->delete([ "type" => "desk", "name" => $name ]);
            }

            /**
             * @inheritDoc
             */

            public function getCards($query) {
                $query["type"] = "card";

                return $this->get($query);
            }

            /**
             * @inheritDoc
             */

            public function upsertCard($card) {
                $card["type"] = "card";

                $exists = $this->collectionExists($this->login);

                $r = $this->put($card);

                if (!$exists) {
                    $this->createIndexes($this->login);
                }

                return $r;
            }

            /**
             * @inheritDoc
             */

            public function deleteCard($id) {
                $desks = $this->getDesks();

                foreach ($desks as $i => $desk) {
                    foreach ($desk["columns"] as $j => $column) {
                        if ($c = array_search($id, $column["cards"]) !== false) {
                            array_splice($desks[$i]["columns"][$j]["cards"], $c, 1);
                            $this->upsertDesk($desks[$i]);
                        }
                    }
                }

                return $this->delete([ "type" => "card", "_id" => $id ]);
            }
        }
    }

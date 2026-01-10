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

                if (@$query["_id"]) {
                    $query["_id"] = new \MongoDB\BSON\ObjectID($query["_id"]);
                }

                if (!$query) {
                    $query = [];
                }

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

                if (@$query["_id"]) {
                    $query["_id"] = new \MongoDB\BSON\ObjectID($query["_id"]);
                }

                $this->mongo->$db->$login->deleteMany($query);

                return true;
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

                return $this->put($desk);
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

                return $this->put($card);
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

<?php

    namespace cli {

        class db {

            function __construct(&$global_cli) {
                $global_cli["#"]["db"]["init-db"] = [
                    "params" => [
                        [
                            "skip" => [
                                "value" => "integer",
                                "placeholder" => "version",
                                "optional" => true,
                            ],
                        ],
                        [
                            "force" => [
                                "value" => "integer",
                                "placeholder" => "version",
                                "optional" => true,
                            ],
                        ],
                        [
                            "set-version" => [
                                "value" => "integer",
                                "placeholder" => "version",
                                "optional" => true,
                            ],
                        ],
                    ],
                    "exec" => [ $this, "init" ],
                    "description" => "Initialize (update) main database",
                ];

                $global_cli["#"]["db"]["backup-db"] = [
                    "exec" => [ $this, "backup" ],
                    "description" => "Backup database",
                ];

                $global_cli["#"]["db"]["list-db-backups"] = [
                    "exec" => [ $this, "list" ],
                    "description" => "List existing database backups",
                ];

                $global_cli["#"]["db"]["restore-db"] = [
                    "exec" => [ $this, "restore" ],
                    "description" => "Restore database from backup",
                    "value" => "string",
                    "placeholder" => "backup filename without path and extension"
                ];

                $global_cli["#"]["db"]["schema"] = [
                    "exec" => [ $this, "schema" ],
                    "value" => "string",
                    "placeholder" => "schema",
                    "description" => "Move RBT tables to specified database schema",
                ];

                $global_cli["#"]["db"]["mongodb-set-fcv"] = [
                    "exec" => [ $this, "fcv" ],
                    "value" => "string",
                    "placeholder" => "version",
                    "description" => "Set MongoDB feature compatibility version",
                ];

                $global_cli["#"]["db"]["mongodb-compact"] = [
                    "exec" => [ $this, "compact" ],
                    "value" => "string",
                    "placeholder" => "database",
                    "description" => "Force run compact for MongoDB database collection",
                    "params" => [
                        [
                            "collection" => [
                                "value" => "string",
                                "placeholder" => "collection",
                            ],
                        ],
                    ],
                ];

                $global_cli["#"]["db"]["mongodb-autocompact"] = [
                    "exec" => [ $this, "autocompact" ],
                    "value" => "string",
                    "placeholder" => "database",
                    "description" => "Enable autocompact for MongoDB database",
                ];
            }

            function init($args) {
                global $db, $redis;

                maintenance(true);

                waitAll();

                $aiids = $redis->keys("aiid_*");
                if ($aiids) {
                    foreach ($aiids as $id) {
                        $acr = explode("_", $id)[1];
                        $redis->set("AIID:" . $acr, $redis->get("aiid_" . $acr));
                        $redis->del("aiid_" . $acr);

                        echo "AIID migrate: $acr\n";
                    }
                    echo "\n";
                }

                $persistents = $redis->keys("persistent_*");
                if ($persistents) {
                    foreach ($persistents as $pid) {
                        $new = explode("_", $pid);
                        array_shift($new);
                        $uid = $new[0];
                        $new = implode(":", $new);
                        $redis->set("PERSISTENT:" . $new, $redis->get($pid));
                        $redis->del($pid);

                        echo "PERSISTENT migrate: $uid\n";
                    }
                    echo "\n";
                }

                backupDB(false);
                echo "\n";

                if (@$args["--set-version"]) {
                    $sth = $db->prepare("update core_vars set var_value = :version where var_name = 'dbVersion'");
                    $sth->bindParam('version', $args["--set-version"]);
                    $sth->execute();
                } else {
                    initDB(@$args["--skip"], @$args["--force"]);
                }

                $n = clearCache(true);
                echo "$n cache entries cleared\n\n";

                reindex();
                echo "\n";

                maintenance(false);

                try {
                    $db->exec("commit");
                } catch (\Exception $e) {
                    //
                }

                exit(0);
            }

            function backup() {
                maintenance(true);

                waitAll();

                backupDB();

                maintenance(false);

                exit(0);
            }

            function list() {
                listDBBackups();

                exit(0);
            }

            function restore($args) {
                maintenance(true);

                waitAll();

                restoreDB($args["--restore-db"]);

                maintenance(false);

                exit(0);
            }

            function schema($args) {
                maintenance(true);

                waitAll();

                schema($args["--schema"]);

                maintenance(false);

                exit(0);
            }

            function fcv($args) {
                global $config;

                maintenance(true);

                waitAll();

                if (@$config["mongo"]["uri"]) {
                    $manager = new \MongoDB\Driver\Manager($config["mongo"]["uri"]);
                } else {
                    $manager = new \MongoDB\Driver\Manager();
                }

                $command = new \MongoDB\Driver\Command([ "setFeatureCompatibilityVersion" => $args["--mongodb-set-fcv"], "confirm" => true ]);

                try {
                    $cursor = $manager->executeCommand('admin', $command);
                } catch(\Exception $e) {
                    die($e->getMessage() . "\n");
                }

                $response = object_to_array($cursor->toArray()[0]);

                if ($response && array_key_exists("ok", $response)) {
                    echo "ok\n";
                } else {
                    print_r($response);
                }

                maintenance(false);

                exit(0);
            }

            function compact($args) {
                global $config;

                maintenance(true);

                waitAll();

                if (@$config["mongo"]["uri"]) {
                    $mongo = new \MongoDB\Client($config["mongo"]["uri"]);
                } else {
                    $mongo = new \MongoDB\Client();
                }

                $db = $args["--mongodb-compact"];

                try {
                    $cursor = $mongo->$db->command([ "compact" => $args["--collection"], "dryRun" => false, "force" => true ]);
                } catch(\Exception $e) {
                    die($e->getMessage() . "\n");
                }

                $response = object_to_array($cursor->toArray()[0]);

                if ($response && array_key_exists("bytesFreed", $response)) {
                    echo "ok: {$response["bytesFreed"]} bytes freed\n";
                } else {
                    print_r($response);
                }

                maintenance(false);

                exit(0);
            }

            function autocompact($args) {
                global $config;

                if (@$config["mongo"]["uri"]) {
                    $mongo = new \MongoDB\Client($config["mongo"]["uri"]);
                } else {
                    $mongo = new \MongoDB\Client();
                }

                $db = $args["--mongodb-autocompact"];

                try {
                    $cursor = $mongo->$db->command([ "autoCompact" => true ]);
                } catch(\Exception $e) {
                    die($e->getMessage() . "\n");
                }

                $response = object_to_array($cursor->toArray()[0]);

                if ($response && array_key_exists("bytesFreed", $response)) {
                    echo "ok: {$response["bytesFreed"]} bytes freed\n";
                } else {
                    print_r($response);
                }

                exit(0);
            }
        }
    }
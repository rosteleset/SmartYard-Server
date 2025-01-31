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
            }

            function init($args) {
                global $db;

                maintenance(true);
                wait_all();

                backup_db(false);
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
                wait_all();

                backup_db();

                maintenance(false);

                exit(0);
            }

            function list() {
                list_db_backups();

                exit(0);
            }

            function restore($args) {
                maintenance(true);
                wait_all();

                restore_db($args["--restore-db"]);

                maintenance(false);

                exit(0);
            }

            function schema($args) {
                maintenance(true);
                wait_all();

                schema($args["--schema"]);

                maintenance(false);

                exit(0);
            }

            function fcv($args) {
                maintenance(true);
                wait_all();

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

                $response = $cursor->toArray()[0];

                echo "ok\n";

                maintenance(false);

                exit(0);
            }
        }
    }
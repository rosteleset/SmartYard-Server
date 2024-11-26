<?php

    namespace cli {

        class db {

            function __construct(&$globalCli) {
                $globalCli["#"]["db"]["init-db"] = [
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

                startup(true);
                echo "\n";

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
        }
    }
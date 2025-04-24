<?php

    namespace cli {

        class init {

            function __construct(&$global_cli) {
                $global_cli["#"]["initialization and update"]["admin-password"] = [
                    "value" => "string",
                    "placeholder" => "password",
                    "description" => "Set (update) admin password",
                    "exec" => [ $this, "password" ],
                ];

                $global_cli["#"]["initialization and update"]["reindex"] = [
                    "description" => "Reindex access to API",
                    "exec" => [ $this, "reindex" ],
                ];

                $global_cli["#"]["initialization and update"]["enter-maintenance-mode"] = [
                    "stage" => "pre",
                    "description" => "Enter to maintenance mode",
                    "exec" => [ $this, "maintenanceOn" ],
                ];

                $global_cli["#"]["initialization and update"]["exit-maintenance-mode"] = [
                    "stage" => "pre",
                    "description" => "Exit from maintenance mode",
                    "exec" => [ $this, "maintenanceOff" ],
                ];

                $global_cli["#"]["initialization and update"]["clear-cache"] = [
                    "description" => "Clear redis cache items",
                    "exec" => [ $this, "cache" ],
                ];

                $global_cli["#"]["initialization and update"]["cleanup"] = [
                    "description" => "Run DB cleanup",
                    "exec" => [ $this, "cleanup" ],
                ];

                $global_cli["#"]["initialization and update"]["update"] = [
                    "description" => "Update client and server from git",
                    "params" => [
                        [
                            "devel" => [
                                "optional" => true,
                            ],
                        ],
                    ],
                    "exec" => [ $this, "update" ],
                ];
            }

            function password($args) {
                global $db;

                //TODO: rewrite to insert method
                try {
                    $db->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
                } catch (\Exception $e) {
                    //
                }

                //TODO: rewrite to modify method
                try {
                    $sth = $db->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
                    $sth->execute([ ":password" => password_hash($args["--admin-password"], PASSWORD_DEFAULT) ]);
                    echo "admin account updated\n\n";
                } catch (\Exception $e) {
                    die("admin account update failed\n\n");
                }

                exit(0);
            }

            function reindex() {
                $n = clearCache(true);
                echo "$n cache entries cleared\n\n";
                reindex();
                echo "\n";

                exit(0);
            }

            function maintenanceOn() {
                maintenance(true);

                exit(0);
            }

            function maintenanceOff() {
                maintenance(false);

                exit(0);
            }

            function cache() {
                $n = clearCache(true);
                echo "$n cache entries cleared\n\n";

                exit(0);
            }

            function cleanup() {
                cleanup();

                exit(0);
            }

            function update($args) {
                global $config;

                maintenance(true);
                wait_all();

                backup_db();
                echo "\n";

                chdir(__DIR__ . "/..");

                $code = false;

                $devel = array_key_exists("--devel", $args);

                system("git pull", $code);
                echo "\n";

                if ($code !== 0) {
                    exit($code);
                }

                $code = false;

                if ($devel) {
                    system("git checkout master", $code);
                } else {
                    system("git checkout `git ls-remote --tags https://github.com/rosteleset/SmartYard-Server | awk \"{print \$2;}\" | cut -d \"/\" -f 3 | sort --version-sort --field-separator=. | tail -n 1`", $code);
                }

                if ($code !== 0) {
                    exit($code);
                }

                initDB();
                echo "\n";

                $clickhouse_config = @$config['clickhouse'];

                $clickhouse = new \clickhouse(
                    @$clickhouse_config['host'] ?? '127.0.0.1',
                    @$clickhouse_config['port'] ?? 8123,
                    @$clickhouse_config['username'] ?? 'default',
                    @$clickhouse_config['password'] ?? 'qqq',
                );

                initClickhouseDB($clickhouse);

                echo "\n";

                $n = clearCache(true);
                echo "$n cache entries cleared\n\n";

                reindex();
                echo "\n";

                maintenance(false);

                exit(0);
            }
        }
    }
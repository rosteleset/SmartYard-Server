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
                        [
                            "pre-release" => [
                                "optional" => true,
                            ],
                        ],
                        [
                            "version" => [
                                "value" => "string",
                                "placeholder" => "version",
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

                $devel = array_key_exists("--devel", $args);

                if ($devel && @$args["--version"]) {
                    \cliUsage();
                }

                if (@$args["--version"]) {
                    $version = $args["--version"];
                } else {
                    $version = @json_decode(file_get_contents("https://api.github.com/repos/rosteleset/SmartYard-Server/releases/latest", false, stream_context_create([ 'http' => [ 'method' => 'GET', 'header' => [ 'User-Agent: PHP', 'Content-type: application/x-www-form-urlencoded' ] ] ])), true)["tag_name"];
                }

                if (!$version) {
                    echo "No releases found\n";
                    exit(2);
                }

                maintenance(true);
                wait_all();

                backup_db();
                echo "\n";

                chdir(__DIR__ . "/..");

                $code = false;

                if ($devel) {
                    $code = false;
                    system("git pull https://github.com/rosteleset/SmartYard-Server main", $code);
                    echo "\n";
                    if ($code !== 0) {
                        exit($code);
                    }

                    $code = false;
                    system("git checkout main", $code);
                    echo "\n";
                    if ($code !== 0) {
                        exit($code);
                    }

                    $code = false;
                    system("git pull", $code);
                    echo "\n";
                    if ($code !== 0) {
                        exit($code);
                    }

                    $version = substr(explode(" ", explode("\n", `git log -1`)[0])[1], 0, 7);
                } else {
                    $code = false;
                    system("git pull https://github.com/rosteleset/SmartYard-Server $version --force", $code);
                    echo "\n";
                    if ($code !== 0) {
                        exit($code);
                    }
                }

                file_put_contents("version", $version);

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
<?php

    //TODO: move this code to ../cli.php after all modifications

    $globalCli = [
        // global part
        "#" => [
            "initialization and update" => [
                "admin-password" => [
                    "value" => "string",
                    "placeholder" => "password",
                    "description" => "Set (update) admin password",
                ],

                "reindex" => [
                    "description" => "Reindex access to API",
                ],

                "exit-maintenance-mode" => [
                    "stage" => "pre",
                    "description" => "Exit from maintenance mode",
                ],

                "clear-cache" => [
                    "description" => "Clear redis cache items",
                ],

                "cleanup" => [
                    "description" => "Clear redis cache items",
                ],

                "update" => [
                    "description" => "Update client and server from git",
                ],

                "init-mobile-issues-project" => [
                ],

                "init-tt-mobile-template" => [
                ],

                "init-monitoring-config" => [
                ],
            ],

            "cron" => [
                "cron" => [
                    "value" => [
                        "minutely",
                        "5min",
                        "hourly",
                        "daily",
                        "monthly",
                    ],
                    "description" => "Run cronpart",
                ],
                "install-crontabs" => [
                    "description" => "Install cronparts",
                ],
                "uninstall-crontabs" => [
                    "description" => "Uninstall cronparts",
                ],
            ],

            "tests" => [
                "check-mail" => [
                    "value" => "string",
                    "placeholder" => "your email address",
                    "description" => "Check email server",
                ],
                "get-db-version" => [

                ],
                "check-backends" => [

                ],
            ],

            "autoconfigure" => [
                "autoconfigure-device" => [
                    "params" => [
                        [
                            "id" => [
                                "value" => "integer",
                                "placeholder" => "device id",
                            ],
                            "first-time" => [
                                "optional" => true,
                            ],
                        ],
                    ],
                    "value" => "string",
                    "placeholder" => "device type",
                    "description" => "Autoconfigure device",
                ],
            ],

            "db" => [
                "backup-db" => [
                    "description" => "Backup database",
                ],
                "list-db-backups" => [
                    "description" => "List existing database backups",
                ],
                "restore-db" => [
                    "description" => "Restore database from backup",
                    "value" => "string",
                    "placeholder" => "backup filename without path and extension"
                ],
                "schema" => [
                    "value" => "string",
                    "placeholder" => "schema",
                    "description" => "Move RBT tables to specified database schema",
                ],
                "mongodb-set-fcv" => [
                    "exec" => "db",
                    "description" => "Set MongoDB feature compatibility version",
                ],
            ],

            "config" => [
                "print-config" => [
                    "description" => "Parse and print server config",
                ],
                "strip-config" => [
                    "description" => "Parse and strip server config (json5->json)",
                ],
            ],
        ],
    ];

    function cli($stage, $backend = "#", $args) {
        global $globalCli;

        $f = false;

        foreach (@$globalCli[$backend] as $title => $part) {
            foreach ($part as $name => $command) {
                if (array_key_exists("--" . $name, $args)) {
                    if (!@$command["stage"]) {
                        $command["stage"] = "run";
                    }
                    if ($command["stage"] == $stage) {
                        $command["exec"]($args);
                        $f = true;
                    }
                }
            }
        }

        if ($f) {
            exit(0);
        }
    }

    function cliUsage() {
        global $globalCli, $argv, $config;

        foreach ($config["backends"] as $b => $p) {
            $i = loadBackend($b);

            if ($i) {
                $c = $i->cliUsage();

                if ($c && is_array($c) && count($c)) {
                    if (!@$globalCli[$b]) {
                        $globalCli[$b] = [];
                    }
                    $globalCli[$b] = array_merge($globalCli[$b], $c);
                }
            }
        }


        foreach ($globalCli as $backend => $cli) {
            if ($backend == "#") {
                echo "usage: {$argv[0]} <params>\n\n";
            } else {
                echo "usage: {$argv[0]} $backend <params>\n\n";
            }

            echo "  common parts:\n\n";
            echo "    --parent-pid=<pid>\n";
            echo "      Set parent pid\n\n";
            echo "    --debug\n";
            echo "      Run with debug\n\n";

            foreach ($cli as $title => $part) {
                echo "  $title:\n\n";

                foreach ($part as $name => $command) {
                    echo "    --$name";
                    if (@$command["value"]) {
                        echo "=<";
                        if (is_array($command["value"])) {
                            echo implode("|", $command["value"]);
                        } else {
                            echo (@$command["placeholder"]) ? $command["placeholder"] : "value";
                        }
                        echo ">";
                    }
                    if (@$command["params"]) {
                        $g = "";
                        foreach ($command["params"] as $paramGroup) {
                            $p = "";
                            foreach ($paramGroup as $prefix => $param) {
                                if (@$param["optional"]) {
                                    $p .= "[";
                                }
                                $p .= "--$prefix";
                                if (@$param["value"]) {
                                    $p .= "=<";
                                    if (is_array($param["value"])) {
                                        $p .= implode("|", $param["value"]);
                                    } else {
                                        $p .= (@$param["placeholder"]) ? $param["placeholder"] : "value";
                                    }
                                    $p .= ">";
                                }
                                if (@$param["optional"]) {
                                    $p .= "]";
                                }
                                $p .= " ";
                            }
                            $g .= trim($p) . " | ";
                        }
                        if ($g) {
                            $g = substr($g, 0, -3);
                        }
                        echo " " . $g;
                    }
                    echo "\n";
                    if (@$command["description"]) {
                        echo "      " . $command["description"] . "\n";
                    }
                    echo "\n";
                }
            }
        }

        exit(0);
    }

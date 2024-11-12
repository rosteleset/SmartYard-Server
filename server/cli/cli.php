<?php

    $globalCli = [
        // global part
        "#" => [
            "demo server" => [
                "run-demo-server" => [
                    "params" => [
                        [
                            "port" => [
                                "value" => "integer",
                                "placeholder" => "8000",
                                "optional" => true,
                            ],
                        ],
                    ],
                    "exec" => "server",
                    // pre db init
                    "stage" => 0,
                ],
            ],

            "initialization and update" => [
                "init-db" => [
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
                    "exec" => "db",
                ],

                "init-clickhouse-db" => [
                    "exec" => "clickhouse",
                ],

                "admin-password" => [
                    "value" => "string",
                    "placeholder" => "password",
                    "exec" => "admin",
                ],

                "reindex" => [
                    "exec" => "reindex",
                ],

                "exit-maintenance-mode" => [
                    "exec" => "maintenance",
                    // post db init, but pre starup
                    "stage" => 1,
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
                    "exec" => "cron",
                ],
                "install-crontabs" => [
                    "exec" => "cron",
                ],
                "uninstall-crontabs" => [
                    "exec" => "cron",
                ],
            ],
        ],
    ];

    function cli($stage, $args) {

    }

    function usage() {
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
                echo "  usage: {$argv[0]}\n\n";
            } else {
                echo "  usage: {$argv[0]} $backend\n\n";
            }

            foreach ($cli as $title => $part) {
                echo "    $title:\n\n";

                foreach ($part as $name => $command) {
                    echo "      --$name";
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
                }
                echo "\n";
            }
        }
    }
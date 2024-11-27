<?php

    //TODO: move this code to ../cli.php after all modifications

    $globalCli = [
        // global part
        "#" => [
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
        global $globalCli, $config;

        if ($config && $config["backends"]) {
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
        }

        foreach (@$globalCli[$backend] as $title => $part) {
            foreach ($part as $name => $command) {
                if (array_key_exists("--" . $name, $args)) {
                    if (!@$command["stage"]) {
                        $command["stage"] = "run";
                    }
                    if ($command["stage"] == $stage) {
                        $m = false;
                        if (@$command["params"]) {
                            foreach ($command["params"] as $variants) {
                                //TODO: add params set check
                                $m = true;
                            }
                        } else {
                            $m = true;
                        }
                        if ($m) {
                            //TODO: add param value check
                            if ($backend == "#") {
                                $command["exec"]($args);
                            } else {
                                $i = loadBackend($backend);
                                $i->cli($args);
                            }
                        }
                    }
                }
            }
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
                        foreach ($command["params"] as $variants) {
                            $p = "";
                            foreach ($variants as $prefix => $param) {
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

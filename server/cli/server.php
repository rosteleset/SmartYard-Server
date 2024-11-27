<?php

    namespace cli {

        class server {

            function __construct(&$global_cli) {
                $global_cli["#"]["demo server"]["run-demo-server"] = [
                    "params" => [
                        [
                            "port" => [
                                "value" => "integer",
                                "placeholder" => "8000",
                                "optional" => true,
                            ],
                        ],
                    ],
                    "exec" => [ $this, "exec" ],
                    "stage" => "init",
                    "description" => "Run demo server for local development",
                ];
            }

            function exec($args) {
                global $db;

                $db = null;

                if (is_executable_pathenv(PHP_BINARY)) {
                    $port = 8000;

                    if (count($args) == 2) {
                        if (array_key_exists("--port", $args) && !empty($args["--port"])) {
                            $port = $args["--port"];
                        } else {
                            \cliUsage();
                        }
                    }

                    echo "open in your browser:\n\n";
                    echo "http://localhost:$port/client/index.html\n\n";
                    chdir(__DIR__ . "/..");
                    putenv("SPX_ENABLED=1");
                    putenv("SPX_REPORT=full");
                    putenv("SPX_AUTO_START=1");
                    passthru(PHP_BINARY . " -S 0.0.0.0:$port");
                } else {
                    die("no php interpreter found in path\n\n");
                }

                exit(0);
            }
        }
    }
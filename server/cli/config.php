<?php

    namespace cli {

        class config {

            function __construct(&$global_cli) {
                $global_cli["#"]["config"]["print-config"] = [
                    "description" => "Parse and print server config",
                    "exec" => [ $this, "print" ],
                ];

                $global_cli["#"]["config"]["strip-config"] = [
                    "description" => "Parse and strip server config (json5->json)",
                    "exec" => [ $this, "strip" ],
                ];
            }

            function print() {
                global $config;

                print_r($config);

                exit(0);
            }

            function strip() {
                global $config;

                file_put_contents("config/config.json", json_encode($config, JSON_PRETTY_PRINT));

                exit(0);
            }
        }
    }
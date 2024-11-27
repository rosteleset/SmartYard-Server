<?php

    namespace cli {

        class cron {

            function __construct(&$globalCli) {
                $globalCli["#"]["cron"]["cron"] = [
                    "value" => [
                        "minutely",
                        "5min",
                        "hourly",
                        "daily",
                        "monthly",
                    ],
                    "description" => "Run cronpart",
                    "exec" => [ $this, "run" ],
                ];

                $globalCli["#"]["cron"]["install-crontabs"] = [
                    "description" => "Install cronparts",
                    "exec" => [ $this, "install" ],
                ];

                $globalCli["#"]["cron"]["uninstall-crontabs"] = [
                    "description" => "Uninstall cronparts",
                    "exec" => [ $this, "uninstall" ],
                ];
            }

            function run($args) {
                global $config;

                $part = $args["--cron"];

                foreach ($config["backends"] as $backend_name => $cfg) {
                    $backend = loadBackend($backend_name);
                    if ($backend) {
                        try {
                            if (!$backend->cron($part)) {
                                echo "$backend_name [$part] fail\n\n";
                            }
                        } catch (\Exception $e) {
                            print_r($e);
                            echo "$backend_name [$part] exception\n\n";
                        }
                    }
                }

                exit(0);
            }

            function install($args) {
                $n = installCrontabs();
                echo "$n crontabs lines added\n\n";
                exit(0);
            }

            function uninstall($args) {
                $n = unInstallCrontabs();
                echo "$n crontabs lines removed\n\n";
                exit(0);
            }
        }
    }
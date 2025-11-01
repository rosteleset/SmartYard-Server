<?php

    namespace cli {

        class cron {

            function __construct(&$global_cli) {
                $global_cli["#"]["cron"]["cron"] = [
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

                $global_cli["#"]["cron"]["install-crontabs"] = [
                    "description" => "Install cronparts",
                    "exec" => [ $this, "install" ],
                ];

                $global_cli["#"]["cron"]["uninstall-crontabs"] = [
                    "description" => "Uninstall cronparts",
                    "exec" => [ $this, "uninstall" ],
                ];

                $global_cli["#"]["cron"]["update-crontabs"] = [
                    "description" => "Update cronparts",
                    "exec" => [ $this, "unpdate" ],
                ];
            }

            function run($args) {
                global $config, $redis;

                if ($part == "daily") {
                    $keys = $redis->keys("CRON:LOCK:*");
                    foreach ($keys as $key) {
                        $pid = (int)$redis->get($key);
                        if (!file_exists("/proc/$pid")) {
                            echo "lock $key found, but process doesn't exists, cleaning\n";
                            $redis->del($key);
                        }
                    }
                }

                $part = $args["--cron"];

                if (!checkStr($part, [ "variants" => [ "minutely", "5min", "hourly", "daily", "monthly", ] ])) {
                    cliUsage();
                }

                foreach ($config["backends"] as $backend_name => $cfg) {
                    $backend = loadBackend($backend_name);
                    if ($backend) {
                        $pid = (int)$redis->get("CRON:LOCK:{$backend_name}:$part");
                        if (!$pid) {
                            $redis->set("CRON:LOCK:{$backend_name}:$part", getmypid());
                            try {
                                if (!$backend->cron($part)) {
                                    echo "$backend_name [$part] fail\n\n";
                                }
                            } catch (\Exception $e) {
                                print_r($e);
                                echo "$backend_name [$part] exception\n\n";
                            }
                            $redis->del("CRON:LOCK:{$backend_name}:$part");
                        } else {
                            if (file_exists("/proc/$pid")) {
                                echo "$backend_name [$part] locked by pid: $pid\n";
                            } else {
                                echo "$backend_name [$part] locked by pid: $pid, but process doesn't exists, cleaning\n";
                                $redis->del("CRON:LOCK:{$backend_name}:$part");
                            }
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

            function unpdate($args) {
                $u = unInstallCrontabs();
                $i = installCrontabs();
                echo "$u crontabs lines removed and $i crontabs lines added\n\n";

                exit(0);
            }
        }
    }
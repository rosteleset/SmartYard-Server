<?php

    namespace cli {

        class tests {

            function __construct(&$global_cli) {
                $global_cli["#"]["tests"]["check-mail"] = [
                    "value" => "string",
                    "placeholder" => "your email address",
                    "description" => "Check email server",
                    "exec" => [ $this, "email" ],
                ];

                $global_cli["#"]["tests"]["get-db-version"] = [
                    "description" => "Show DB schema version",
                    "exec" => [ $this, "version" ],
                ];

                $global_cli["#"]["tests"]["check-backends"] = [
                    "description" => "Check backends \"health\"",
                    "exec" => [ $this, "backends" ],
                ];
            }

            function email($args) {
                global $config;

                $r = email($config, $args["--check-mail"], "test email", "test email");
                if ($r === true) {
                    echo "email sended\n\n";
                } else
                if ($r === false) {
                    echo "no email config found\n\n";
                } else {
                    print_r($r);
                }

                exit(0);
            }

            function version() {
                global $version;

                echo "dbVersion: $version\n\n";

                exit(0);
            }

            function backends($args) {
                global $config;

                $all_ok = true;

                foreach ($config["backends"] as $backend => $null) {
                    $t = loadBackend($backend);
                    if (!$t) {
                        echo "loading $backend failed\n\n";
                        $all_ok = false;
                    } else {
                        try {
                            if (!$t->check()) {
                                echo "error checking backend $backend\n\n";
                                $all_ok = false;
                            }
                        } catch (\Exception $e) {
                            print_r($e);
                            $all_ok = false;
                        }
                    }
                }

                if ($all_ok) {
                    echo "everything is all right\n\n";
                }

                exit(0);
            }
        }
    }
<?php

    namespace cli {

        class templates {

            function __construct(&$global_cli) {
                $global_cli["#"]["initialization and update"]["init-mobile-issues-project"] = [
                    "exec" => [ $this, "mobile1" ],
                ];

                $global_cli["#"]["initialization and update"]["init-tt-mobile-template"] = [
                    "exec" => [ $this, "mobile2" ],
                ];

                $global_cli["#"]["initialization and update"]["init-monitoring-config"] = [
                    "exec" => [ $this, "monitoring" ],
                ];
            }

            function mobile1() {
                init_mp();

                exit(0);
            }

            function mobile2() {
                try {
                    installTTMobileTemplate();
                } catch (\Exception $e) {
                    die($e->getMessage() . "\n\n");
                }

                exit(0);
            }

            function monitoring() {
                try {
                    $monitoring = loadBackend('monitoring');
                    $monitoring->configureMonitoring();
                } catch (\Exception $e) {
                    die($e->getMessage() . "\n\n");
                }

                exit(0);
            }
        }
    }

<?php

    namespace cli {

        class clickhouse {

            function __construct(&$global_cli) {
                $global_cli["#"]["db"]["init-clickhouse-db"] = [
                    "exec" => [ $this, "init" ],
                    "description" => "Initialize (update) clickhouse database",
                ];
            }

            function init($args) {
                global $config;

                $clickhouse_config = @$config['clickhouse'];

                initClickhouseDB(new \clickhouse(
                    @$clickhouse_config['host'] ?? '127.0.0.1',
                    @$clickhouse_config['port'] ?? 8123,
                    @$clickhouse_config['username'] ?? 'default',
                    @$clickhouse_config['password'] ?? 'qqq',
                ));

                exit(0);
            }
        }
    }
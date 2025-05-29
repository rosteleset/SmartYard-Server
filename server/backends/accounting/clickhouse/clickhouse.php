<?php

    /**
     * "clickhouse" accounting (logging) class
     */

    namespace backends\accounting {

        /**
         * clickhouse accounting (logging) class
         */

        class clickhouse extends accounting {

            /**
             * @var object $clickhouse clickhouse db
             */

            protected $clickhouse;

            /**
             * @param $config
             * @param $db
             * @param $redis
             */

            function __construct($config, $db, $redis, $login = false) {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host']?:'127.0.0.1',
                    @$config['clickhouse']['port']?:8123,
                    @$config['clickhouse']['username']?:'default',
                    @$config['clickhouse']['password']?:'qqq',
                    @$config['clickhouse']['database']?:'default'
                );
            }

            /**
             * @param $params
             * @param $code
             * @return void
             */

            public function log($params, $code) {
                $login = $this->login;

                if (@$params["_id"]) {
                    $msg = "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}/{$params["_id"]}";
                } else {
                    $msg = "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}";
                }

                $this->raw($params["_ip"], "frontend", $msg);
            }

            /**
             * @inheritDoc
             */

            public function raw($ip, $unit, $msg) {
                return $this->clickhouse->insert("syslog", [ [ "date" => time(), "ip" => $ip, "unit" => $unit, "msg" => $msg ] ]);
            }

            /**
             * @inheritDoc
             */

            public function get($query) {
                // TODO: Implement get() method.
            }
        }
    }

<?php

    /**
     * "clickhouse" accounting (logging) class
     */

    namespace backends\accounting {

        /**
         * "silent" accounting (logging) class
         */

        class clickhouse extends accounting {
            private $clickhouse;

            /**
             * @param $config
             * @param $db
             * @param $redis
             */
            function __construct($config, $db, $redis)
            {
                parent::__construct($config, $db, $redis);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    $config['backends']['accounting']['clickhouse']['host'],
                    $config['backends']['accounting']['clickhouse']['port'],
                    $config['backends']['accounting']['clickhouse']['username'],
                    $config['backends']['accounting']['clickhouse']['password'],
                    $config['backends']['accounting']['clickhouse']['database']
                );
            }

            /**
             * @param $params
             * @param $code
             * @return void
             */
            public function log($params, $code) {
                $login = @($params["_login"]?:$params["login"]);
                $login = $login?:"-";

                if (@$params["_id"]) {
                    $msg = "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}/{$params["_id"]}";
                } else {
                    $msg = "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}";
                }

                $this->clickhouse->insert("syslog", [ [ "date" => $this->db->now(false), "ip" => $params["_ip"], "unit" => "frontend", "msg" => $msg ] ]);
            }


            /**
             * @inheritDoc
             */
            public function raw($ip, $unit, $msg)
            {
                $this->clickhouse->insert("syslog", [ [ "date" => $this->db->now(false), "ip" => $ip, "unit" => $unit, "msg" => $msg ] ]);
            }
        }
    }

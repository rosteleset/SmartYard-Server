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
             * @param object $params all params passed to api handlers
             * @param integer $code return code
             * @return void
             */

            function __construct($config, $db, $redis)
            {
                parent::__construct($config, $db, $redis);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse($config['backends']['accounting']['host'], $config['backends']['accounting']['port'], $config['backends']['accounting']['username'], $config['backends']['accounting']['password']);
            }

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
        }
    }

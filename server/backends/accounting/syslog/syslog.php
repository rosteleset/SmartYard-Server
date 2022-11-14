<?php

    /**
     * backends accounting namespace
     */

    namespace backends\accounting {

        /**
         * syslog accounting (logging) class
         */

        class syslog extends accounting {

            /**
             * @param object $config link to config structute
             * @param object $db link to default PDO database object
             * @param object $redis link to redis object
             *
             * @return void
             */

            public function __construct($config, $db, $redis) {
                parent::__construct($config, $db, $redis);

                openlog("rbt", LOG_ODELAY, LOG_USER);
            }

            /**
             * @param object $params all params passed to api handlers
             * @param integer $code return code
             * @return void
             */

            public function log($params, $code) {
                $login = @($params["_login"]?:$params["login"]);
                $login = $login?:"-";

                if (@$params["_id"]) {
                    syslog(LOG_INFO, "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}/{$params["_id"]}");
                } else {
                    syslog(LOG_INFO, "{$params["_ip"]}:{$_SERVER['REMOTE_PORT']} [$code] $login {$params["_request_method"]} {$params["_path"]["api"]}/{$params["_path"]["method"]}");
                }
            }

            /**
             * for closing syslog only
             */

            public function __destruct() {
                closelog();
            }

            /**
             * @inheritDoc
             */
            public function raw($ip, $unit, $msg)
            {
                syslog(LOG_INFO, "{$ip} [$unit] $msg");
            }
        }
    }

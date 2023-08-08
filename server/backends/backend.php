<?php

    /**
     * backends namespace
     */

    namespace backends {
        /**
         * base class for all backends
         */

        abstract class backend {

            /**
             * @var object $config link to config structute
             * @var object $db link to default PDO database object
             * @var object $redis link to redis object
             */

            protected $config, $db, $redis, $login, $uid;

            /**
             * default constructor
             *
             * @param object $config link to config structute
             * @param object $db link to default PDO database object
             * @param object $redis link to redis object
             *
             * @return void
             */

            public function __construct($config, $db, $redis, $login = false) {
                global $params;

                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
                $this->login = $login ? : ((is_array($params) && array_key_exists("_login", $params)) ? $params["_login"] : "-");
                
                switch ($this->login) {
                    case "-":
                        $this->uid = -1;
                        break;
                    case "admin":
                        $this->uid = 0;
                        break;
                    default:
                        $this->uid = loadBackend("users")->getUidByLogin($this->login);
                        break;
                }
            }

            /**
             * returns class capabilities
             *
             * @return mixed
             */

            public function capabilities() {
                return false;
            }

            /**
             * garbage collector
             *
             * @return boolean
             */

            public function cleanup() {
                return false;
            }

            /**
             * access rights regulator
             *
             * @param $params
             * @return boolean
             */

            public function allow($params) {
                return false;
            }

            /**
             * check if object is used in backend
             * for example, usage("house", 4474)
             *
             * @return boolean
             */

            public function usage($object, $id) {
                return false;
            }

            /**
             * @param $part = [ 'minutely', '5min', 'hourly', 'daily', 'monthly' ]
             * @return false
             */

            public function cron($part) {
                return true;
            }

            /**
             * @return bool
             */
            public function check() {
                return true;
            }

            /**
             * @param $uid integer
             * @param $login string
             * @return void
             */
            public function setCreds($uid, $login) {
                $this->uid = $uid;
                $this->login = $login;
            }

            /**
             * @param $login
             */
            public function setLogin($login)
            {
                if ($login != $this->login) {
                    $this->setCreds(loadBackend("users")->getUidByLogin($login), $login);
                }
            }

            /**
             * @param $key
             * @param $value
             * @return mixed
             */
            public function cache($key, $value = null)
            {
                if (strpos($key, ":") === false) {
                    $key = $key . ":";
                }

                if ($value) {
                    $this->redis->setex("CACHE:" . strtoupper($this->backend) . ":" . $key, 3 * 24 * 60 * 60, serialize($value));
                    return false;
                }

                if ($value === false) {
                    $this->del("CACHE:" . strtoupper($this->backend) . ":" . $key);
                    return false;
                }

                $value = $this->redis->get("CACHE:" . strtoupper($this->backend) . ":" . $key);

                if ($value) {
                    return unserialize($value);
                }

                return false;
            }

            /**
             * @return mixed
             */
            public function clearCache()
            {
                $_keys = $this->redis->keys("CACHE:" . strtoupper($this->backend) . ":*");

                foreach ($_keys as $_key) {
                    $this->redis->del($_key);
                    $n++;
                }

                return $n;
            }
        }
    }

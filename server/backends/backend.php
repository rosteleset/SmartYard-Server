<?php

/**
 * backends namespace
 */

namespace backends {

    use Redis;
    use Selpol\Service\DatabaseService;

    /**
     * base class for all backends
     */
    abstract class backend
    {
        protected int $uid;

        protected array $config;

        protected DatabaseService $db;
        protected Redis $redis;

        protected string $login;

        public function __construct(array $config, DatabaseService $db, Redis $redis, bool $login = false)
        {
            global $params;

            $this->config = $config;

            $this->db = $db;
            $this->redis = $redis;

            $this->login = $login ?: ((is_array($params) && array_key_exists("_login", $params)) ? $params["_login"] : "-");

            $this->uid = match ($this->login) {
                "-" => -1,
                "admin" => 0,
                default => backend("users")->getUidByLogin($this->login),
            };
        }

        /**
         * returns class capabilities
         *
         * @return mixed
         */

        public function capabilities()
        {
            return false;
        }

        /**
         * garbage collector
         *
         * @return boolean
         */

        public function cleanup()
        {
            return false;
        }

        /**
         * access rights regulator
         *
         * @param $params
         * @return boolean
         */

        public function allow($params)
        {
            return false;
        }

        /**
         * check if object is used in backend
         * for example, usage("house", 4474)
         *
         * @return boolean
         */

        public function usage($object, $id)
        {
            return false;
        }

        /**
         * @param $part = [ 'minutely', '5min', 'hourly', 'daily', 'monthly' ]
         * @return false
         */

        public function cron($part)
        {
            return true;
        }

        /**
         * @return bool
         */
        public function check()
        {
            return true;
        }

        /**
         * @param $uid integer
         * @param $login string
         * @return void
         */
        public function setCreds($uid, $login)
        {
            $this->uid = $uid;
            $this->login = $login;
        }
    }
}

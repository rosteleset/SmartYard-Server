<?php

    namespace tt\workflow {

        class workflow {

            /**
             * @var object $config link to config structute
             * @var object $db link to default PDO database object
             * @var object $redis link to redis object
             */

            protected $config, $db, $redis, $tt, $sandbox;

            /**
             * default constructor
             *
             * @param object $config link to config structute
             * @param object $db link to default PDO database object
             * @param object $redis link to redis object
             *
             * @return void
             * @throws \Exception
             */

            public function __construct($config, $db, $redis, $tt, $workflow, $sandbox) {
                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
                $this->tt = $tt;
                $this->sandbox = $sandbox;

                $file = __DIR__ . "/workflows/" . $workflow . ".lua";

                if (!file_exists($file)) {
                    $file = false;
                }

                if ($file) {
                    $code = $this->sandbox->loadString(file_get_contents($file));
                    $code->call();
                } else {
                    throw new \Exception("workflow not found");
                }
            }

            /**
             * @param $name
             * @param $arguments
             * @return mixed
             */
            public function __call($name, $arguments)
            {
                $ret = @$this->sandbox->callFunction($name, ...$arguments);

                if (isset($ret) && is_array($ret) && isset($ret[0])) {
                    return $ret[0];
                } else {
                    return $ret;
                }
            }
        }
    }
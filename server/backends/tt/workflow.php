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
             */

            public function __construct($config, $db, $redis, $tt, $workflow) {
                $this->config = $config;
                $this->db = $db;
                $this->redis = $redis;
                $this->tt = $tt;
                $this->sandbox = new \LuaSandbox;

                $file = __DIR__ . "/workflows/" . $workflow . ".lua";
                $customDir = __DIR__ . "/workflowsCustom";
                $fileCustom = $customDir . "/" . $workflow . ".lua";

                if (file_exists($customDir) && file_exists($fileCustom)) {
                    $file = $fileCustom;
                } else
                if (!file_exists($file)) {
                    $file = false;
                }

                if ($file) {
                    $code = $this->sandbox->loadString(file_get_contents($file));
                    $code->call();
                } else {
                    throw new Exception("workflow not found");
                }
            }

            public function __call($name, $arguments)
            {
                error_log("Calling lua method '$name' " . implode(', ', $arguments));

                $ret = $this->sandbox->callFunction($name, $arguments);

                if ($ret && $ret[0]) {
                    return $ret[0];
                } else {
                    return $ret;
                }
            }
        }
    }
<?php

    class vars {
        function __get($var) {
            global $redis;

            return json_decode($redis->get("var_" . md5($var)), true);
        }

        function __set($var, $value) {
            global $redis;

            $redis->set("var_" . md5($var), json_encode($value));
        }

        function __unset($var) {
            global $redis;

            $redis->del("var_" . md5($var));
        }

        function __isset($var) {
            global $redis;

            return count($redis->keys("var_" . md5($var))) > 0;
        }
    }

    $persistent = new vars();
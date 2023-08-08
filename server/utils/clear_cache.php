<?php

    function clearCache($uid) {
        global $redis, $config;

        $keys = [];

        $n = 0;

        if ($uid === true) {
            $keys = $redis->keys("cache_*");
            foreach ($config["backends"] as $backend => $config) {
                $keys = $redis->keys("CACHE:" . strtoupper($backend) . ":*");

                foreach ($keys as $key) {
                    $redis->del($key);
                    $n++;
                }
            }
        } else {
            if (checkInt($uid)) {
                $keys = $redis->keys("CACHE:*_$uid");
            }
        }

        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }

        return $n;
    }
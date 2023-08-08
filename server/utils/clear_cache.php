<?php

    function clearCache($uid) {
        global $redis, $config;

        $keys = [];

        $n = 0;

        if ($uid === true) {
            $keys = $redis->keys("cache_*");
            foreach ($config["backends"] as $backend => $config) {
                $backend = loadBackend($backend);

                error_log($backend);

                if ($backend) {
                    $n += $backend->clearCache();
                }
            }
        } else {
            if (checkInt($uid)) {
                $keys = $redis->keys("cache_*_$uid");
            }
        }

        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }

        return $n;
    }
<?php

    function clearCache($uid) {
        global $redis, $config;

        $keys = [];

        $n = 0;

        if ($uid === true) {
            $keys = $redis->keys("CACHE:*");
        } else {
            if (checkInt($uid)) {
                $keys = $redis->keys("CACHE:FRONT:*:$uid");
            }
        }

        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }

        return $n;
    }
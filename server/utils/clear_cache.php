<?php

    function clearCache($uid) {
        global $redis;

        $keys = [];

        if ($uid === true) {
            $keys = $redis->keys("cache_*");
        } else {
            if (checkInt($uid)) {
                $keys = $redis->keys("cache_*_$uid");
            }
        }

        $n = 0;
        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }

        return $n;
    }
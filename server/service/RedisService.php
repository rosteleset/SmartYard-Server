<?php

namespace Selpol\Service;

use Redis;
use Selpol\Container\ContainerDispose;

class RedisService implements ContainerDispose
{
    private Redis $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    function dispose()
    {
        $this->redis->close();
    }
}
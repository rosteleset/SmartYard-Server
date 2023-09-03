<?php

namespace Selpol\Service;

use Redis;
use RedisException;
use Selpol\Container\ContainerDispose;

class RedisService implements ContainerDispose
{
    private Redis $redis;

    /**
     * @throws RedisException
     */
    public function __construct()
    {
        $this->redis = new Redis();

        $this->redis->connect(config('redis.host'), config('redis.port'));

        if (config('redis.password'))
            $this->redis->auth(config('redis.password'));
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * @throws RedisException
     */
    function dispose(): void
    {
        $this->redis->close();
    }
}
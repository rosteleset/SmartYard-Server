<?php

use Selpol\Cache\RedisCache;
use Selpol\Container\Container;
use Selpol\Service\CameraService;
use Selpol\Service\DatabaseService;
use Selpol\Service\DomophoneService;
use Selpol\Service\RedisService;
use Selpol\Service\TaskService;

return static function (Container $container) {
    $container->singleton(Redis::class, static function () {
        $redis = new Redis();

        $redis->connect(config('redis.host'), config('redis.port'));

        if (config('redis.password'))
            $redis->auth(config('redis.password'));

        return $redis;
    });

    $container->singleton(RedisService::class, static fn(Container $container) => new RedisService($container->get(Redis::class)));
    $container->singleton(DatabaseService::class, static fn() => new DatabaseService(config('db.dsn'), config('db.username'), config('db.password'), config('db.options')));
    $container->singleton(TaskService::class, static fn() => new TaskService());

    $container->singleton(CameraService::class, static fn() => new CameraService());
    $container->singleton(DomophoneService::class, static fn() => new DomophoneService());

    $container->singleton(RedisCache::class, static fn(Container $container) => new RedisCache($container->get(RedisService::class)));
};
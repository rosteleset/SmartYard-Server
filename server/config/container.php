<?php

use Selpol\Cache\FileCache;
use Selpol\Cache\RedisCache;
use Selpol\Container\ContainerBuilder;
use Selpol\Service\AuthService;
use Selpol\Service\BackendService;
use Selpol\Service\CameraService;
use Selpol\Service\FrsService;
use Selpol\Service\DatabaseService;
use Selpol\Service\DomophoneService;
use Selpol\Service\HttpService;
use Selpol\Service\RedisService;
use Selpol\Service\TaskService;

return static function (ContainerBuilder $builder) {
    $builder->singleton(RedisService::class);
    $builder->singleton(DatabaseService::class);
    $builder->singleton(TaskService::class);

    $builder->singleton(HttpService::class);
    $builder->singleton(FrsService::class);

    $builder->singleton(CameraService::class);
    $builder->singleton(DomophoneService::class);
    $builder->singleton(BackendService::class);

    $builder->singleton(AuthService::class);

    $builder->singleton(FileCache::class);
    $builder->singleton(RedisCache::class);
};
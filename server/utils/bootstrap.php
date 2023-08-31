<?php

use Selpol\Container\Container;
use Selpol\Service\DatabaseService;
use Selpol\Service\TaskService;

if (!function_exists('bootstrap')) {
    function bootstrap(): Container
    {
        $container = Container::instance();

        $container->singleton('env', static fn() => env());
        $container->singleton("config", static fn() => config());

        $container->singleton(Redis::class, static function (Container $container) {
            $config = $container->get('config');

            $redis = new Redis();

            $redis->connect($config['redis']['host'], $config['redis']['port']);

            if (array_key_exists('password', $config['redis']))
                $redis->auth($config['redis']['password']);

            return $redis;
        });

        $container->singleton(DatabaseService::class, static function (Container $container) {
            $config = $container->get('config');

            return new DatabaseService(@$config['db']['dsn'], @$config['db']['username'], @$config['db']['password'], @$config['db']['options']);
        });

        $container->singleton(TaskService::class, static fn() => TaskService::instance());

        return $container;
    }
}

if (!function_exists('bootstrap_if_need')) {
    function bootstrap_if_need(): Container
    {
        if (Container::hasInstance())
            return Container::instance();

        return bootstrap();
    }
}
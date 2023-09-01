<?php

use backends\backend;
use Psr\Container\ContainerExceptionInterface;
use Selpol\Service\DatabaseService;
use Selpol\Service\RedisService;

$backends = [];

if (!function_exists('backend')) {
    /**
     * loads backend module by config, returns false if backend not found or can't be loaded
     *
     * @param string $backend module name
     * @param bool $login
     * @return false|backend
     * @throws ContainerExceptionInterface
     */
    function backend(string $backend, bool $login = false): backend|false
    {
        global $backends;

        if (@$backends[$backend])
            return $backends[$backend];
        else {
            if (config('backends.' . $backend)) {
                $config = config('backends.' . $backend);

                try {
                    if (file_exists(path('backends/' . $backend . '/' . $backend . '.php')) && !class_exists("backends\\$backend\\$backend"))
                        require_once path('backends/' . $backend . '/' . $backend . '.php');

                    if (file_exists(path("backends/$backend/" . $config["backend"] . "/" . $config["backend"] . ".php"))) {
                        require_once path("backends/$backend/" . $config["backend"] . "/" . $config["backend"] . ".php");

                        $className = "backends\\$backend\\" . $config["backend"];
                        $backends[$backend] = new $className(config(), container(DatabaseService::class), container(RedisService::class)->getRedis(), $login);

                        return $backends[$backend];
                    } else return false;
                } catch (Exception) {
                    last_error("cantLoadBackend");

                    return false;
                }
            } else {
                last_error("backendNotFound");

                return false;
            }
        }
    }
}
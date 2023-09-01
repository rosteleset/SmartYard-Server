<?php

namespace Selpol\Service;

use backends\backend;
use Throwable;

class BackendService
{
    /** @var backend[] $backends */
    private array $backends = [];

    public function get(string $backend, bool $login = false): backend|false
    {
        if (array_key_exists($backend, $this->backends))
            return $this->backends[$backend];
        else {
            if (config('backends.' . $backend)) {
                $config = config('backends.' . $backend);

                try {
                    if (file_exists(path('backends/' . $backend . '/' . $backend . '.php')) && !class_exists("backends\\$backend\\$backend"))
                        require_once path('backends/' . $backend . '/' . $backend . '.php');

                    if (file_exists(path("backends/$backend/" . $config["backend"] . "/" . $config["backend"] . ".php"))) {
                        require_once path("backends/$backend/" . $config["backend"] . "/" . $config["backend"] . ".php");

                        $className = "backends\\$backend\\" . $config["backend"];

                        $this->backends[$backend] = new $className(config(), container(DatabaseService::class), container(RedisService::class)->getRedis(), $login);

                        return $this->backends[$backend];
                    } else return false;
                } catch (Throwable) {
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
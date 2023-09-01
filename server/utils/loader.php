<?php

use backends\backend;
use hw\cameras\cameras;
use hw\domophones\domophones;
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

if (!function_exists('domophone')) {
    /**
     * loads domophone class, returns false if .json or class not found
     *
     * @param string $model .json
     * @param string $url
     * @param string $password
     * @param boolean $first_time
     * @return false|domophones
     */
    function domophone(string $model, string $url, string $password, bool $first_time = false): domophones|false
    {
        $path_to_model = path('hw/domophones/models/' . $model);

        if (file_exists($path_to_model)) {
            $class = @json_decode(file_get_contents($path_to_model), true)['class'];

            $directory = new RecursiveDirectoryIterator(path('hw/domophones/'));
            $iterator = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $file) {
                if ($file->getFilename() == "$class.php") {
                    $path_to_class = $file->getPath() . "/" . $class . ".php";

                    require_once $path_to_class;

                    $className = "hw\\domophones\\$class";

                    return new $className($url, $password, $first_time);
                }
            }
        }

        return false;
    }
}

if (!function_exists('camera')) {
    /**
     * loads camera class, returns false if .json or class not found
     *
     * @param string $model .json
     * @param string $url
     * @param string $password
     * @param boolean $first_time
     * @return false|cameras
     */
    function camera(string $model, string $url, string $password, bool $first_time = false): cameras|false
    {
        $path_to_model = path('hw/cameras/models/' . $model);

        if (file_exists($path_to_model)) {
            $class = @json_decode(file_get_contents($path_to_model), true)['class'];

            $directory = new RecursiveDirectoryIterator(path('hw/cameras/'));
            $iterator = new RecursiveIteratorIterator($directory);

            foreach ($iterator as $file) {
                if ($file->getFilename() == "$class.php") {
                    $path_to_class = $file->getPath() . "/" . $class . ".php";

                    require_once $path_to_class;

                    $className = "hw\\cameras\\$class";

                    return new $className($url, $password, $first_time);
                }
            }
        }

        return false;
    }
}
<?php

use Selpol\Container\Container;

if (!function_exists('bootstrap')) {
    function bootstrap(): Container
    {
        mb_internal_encoding("UTF-8");

        $container = Container::instance();
        $container->file(path('config/container.php'));

        return $container;
    }
}
<?php

use Selpol\Container\Container;

if (!function_exists('bootstrap')) {
    function bootstrap(): Container
    {
        mb_internal_encoding("UTF-8");

        return Container::file(path('config/container.php'));
    }
}
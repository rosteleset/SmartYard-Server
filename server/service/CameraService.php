<?php

namespace Selpol\Service;

use hw\cameras\cameras;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CameraService
{
    public function model(string $model, string $url, string $password, bool $first_time = false): cameras|false
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
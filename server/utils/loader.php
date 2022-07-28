<?php

    /**
     * loads backend module by config, returns false if backend not found or can't be loaded
     *
     * @param string $backend module name
     * @return false|object
     */

    function loadBackend($backend) {
        global $config, $db, $redis, $backends;

        if (@$backends[$backend]) {
            return $backends[$backend];
        } else {
            if (@$config["backends"][$backend]) {
                try {
                    if (file_exists("backends/$backend/$backend.php") && !class_exists("backends\\$backend\\$backend")) {
                        require_once "backends/$backend/$backend.php";
                    }
                    require_once "backends/$backend/" . $config["backends"][$backend]["backend"] . "/" . $config["backends"][$backend]["backend"] . ".php";
                    $className = "backends\\$backend\\" . $config["backends"][$backend]["backend"];
                    $backends[$backend] = new $className($config, $db, $redis);
                    return $backends[$backend];
                } catch (Exception $e) {
                    setLastError("cantLoadBackend");
                    return false;
                }
            } else {
                setLastError("backendNotFound");
                return false;
            }
        }
    }

    /**
     * loads domophone class, returns false if .json or class not found
     *
     * @param string $model .json
     * @param string $url
     * @param string $password
     * @param boolean $first_time
     * @return false|object
     */

    function loadDomophone($model, $url, $password, $first_time = false) {
        $path_to_model = __DIR__ . "/../hw/domophones/models/$model";

        if (file_exists($path_to_model)) {
            $class = @json_decode(file_get_contents($path_to_model), true)['class'];

            $directory = new RecursiveDirectoryIterator(__DIR__ . "/../hw/domophones/");
            $iterator = new RecursiveIteratorIterator($directory);

            foreach($iterator as $file) {
                if ($file->getFilename() == "$class.php") {
                    $path_to_class = $file->getPath() . "/" . $class . ".php";
                    require_once $path_to_class;
                    return new ("hw\\domophones\\$class")($url, $password, $first_time);
                }
            }
        }

        return false;
    }

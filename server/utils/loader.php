<?php

    /**
     * loads backend module by config, returns false if backend not found or can't be loaded
     *
     * @param string $backend module name
     * @return false|object
     */

    function loadBackend($backend, $login = false) {
        global $config, $db, $redis, $backends;

        if (@$backends[$backend]) {
            if ($login) {
                $backends[$backend]->setLogin($login);
            }
            return $backends[$backend];
        } else {
            if (@$config["backends"][$backend]) {
                try {
                    if (file_exists(__DIR__ . "/../backends/$backend/$backend.php") && !class_exists("backends\\$backend\\$backend")) {
                        require_once __DIR__ . "/../backends/$backend/$backend.php";
                    }
                    if (file_exists(__DIR__ . "/../backends/$backend/" . $config["backends"][$backend]["backend"] . "/" . $config["backends"][$backend]["backend"] . ".php")) {
                        require_once __DIR__ . "/../backends/$backend/" . $config["backends"][$backend]["backend"] . "/" . $config["backends"][$backend]["backend"] . ".php";
                        $className = "backends\\$backend\\" . $config["backends"][$backend]["backend"];
                        $backends[$backend] = new $className($config, $db, $redis, $login);
                        $backends[$backend]->backend = $backend;
                        return $backends[$backend];
                    } else {
                        return false;
                    }
                } catch (Exception $e) {
                    setLastError(i18n("cantLoadBackend", $backend));
                    return false;
                }
            } else {
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
                    $className = "hw\\domophones\\$class";
                    return new $className($url, $password, $first_time);
                }
            }
        }

        return false;
    }

/**
 * loads camera class, returns false if .json or class not found
 *
 * @param string $model .json
 * @param string $url
 * @param string $password
 * @param boolean $first_time
 * @return false|object
 */

function loadCamera($model, $url, $password, $first_time = false) {
    $path_to_model = __DIR__ . "/../hw/cameras/models/$model";

    if (file_exists($path_to_model)) {
        $class = @json_decode(file_get_contents($path_to_model), true)['class'];

        $directory = new RecursiveDirectoryIterator(__DIR__ . "/../hw/cameras/");
        $iterator = new RecursiveIteratorIterator($directory);

        foreach($iterator as $file) {
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

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
                    $backends[$backend] = new ("backends\\$backend\\" . $config["backends"][$backend]["backend"])($config, $db, $redis);
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

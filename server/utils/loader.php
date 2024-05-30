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
     * Loads a device class and returns an instance of the class.
     *
     * This function is used to load and initialize hardware device classes dynamically
     * based on the device type and model specified in a JSON file.
     *
     * @param string $type Device type (e.g., "domophone" or "camera").
     * @param string $model The filename of the JSON model configuration.
     * @param string $url The URL for the device.
     * @param string $password The password for the device.
     * @param bool $firstTime Indicates if it's the first time using the device. Default is false.
     *
     * @return object An object instance of the device class if found and loaded successfully.
     *
     * @throws Exception If there was an error loading the class.
     */
    function loadDevice(string $type, string $model, string $url, string $password, bool $firstTime = false) {
        require_once __DIR__ . '/parse_url_ext.php';
        require_once __DIR__ . '/../hw/autoload.php';

        $availableTypes = ['camera', 'domophone'];

        if (!in_array($type, $availableTypes)) {
            $availableTypesString = implode(', ', array_map(fn($type) => "'$type'", $availableTypes));
            throw new Exception("Invalid device type: '$type'. Available types: $availableTypesString");
        }

        $pathToModel = __DIR__ . "/../hw/ip/$type/models/$model";

        if (!file_exists($pathToModel)) {
            throw new Exception("Model '$model' not found for type '$type'");
        }

        $data = json_decode(file_get_contents($pathToModel), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decoding JSON for model '$model': " . json_last_error_msg());
        }

        $class = $data['class'] ?? null;
        $vendor = strtolower($data['vendor'] ?? '');
        if (!$class || !$vendor) {
            throw new Exception("Invalid model configuration for '$model'");
        }

        $className = "hw\\ip\\$type\\$vendor\\$class";
        $classPath = __DIR__ . "/../hw/ip/$type/$vendor/$class.php";

        if (file_exists($classPath) && class_exists($className)) {
            return new $className($url, $password, $firstTime);
        }

        throw new Exception("Class '$className' not found for model '$model'");
    }

    /**
     * Loads configuration from JSON or YAML files.
     *
     * @return array|false The configuration array, or false if the configuration files are not found or invalid.
     */
    function loadConfiguration()
    {
        try {
            $config = false;

            $jsonConfigPath = __DIR__ . "/../config/config.json";
            $yamlConfigPath = __DIR__ . "/../config/config.yml";

            if (file_exists($jsonConfigPath)) {
                $config = json_decode(file_get_contents($jsonConfigPath), true);
            }

            if (!$config && file_exists($yamlConfigPath)) {
                $config = yaml_parse_file($yamlConfigPath);
            }

            if (!$config) {
                throw new Exception('Configuration files not found or invalid.');
            }

            return $config;
        } catch (Exception $e) {
            error_log($e->getMessage(), 0);
            return $e;
        }
    }

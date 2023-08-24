<?php

/**
 * loads backend module by config, returns false if backend not found or can't be loaded
 *
 * @param string $backend module name
 * @return false|object
 */

/**
 * load env file
 */
function loadEnvFile(): array
{
    $path = dirname(__FILE__) . '/../.env';

    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = explode(PHP_EOL, $content);

        $result = [];

        for ($i = 0; $i < count($lines); $i++) {
            $value = explode('=', $lines[$i], 2);

            if (count($value) == 2) {
                $result[$value[0]] = $value[1];

                putenv($value[0] . '=' . $value[1]);
            }
        }

        return $result;
    }

    return [];
}

/**
 * load env config
 *
 * @param array $env
 * @param array $value
 * @return array
 */
function loadEnv(array $env, array $value): array
{
    $keys = array_keys($value);
    $matches = [];

    for ($i = 0; $i < count($keys); $i++) {
        if (is_array($value[$keys[$i]]))
            $value[$keys[$i]] = loadEnv($env, $value[$keys[$i]]);
        else if (str_starts_with($value[$keys[$i]], '${') && str_ends_with($value[$keys[$i]], '}')) {
            $key = substr($value[$keys[$i]], 2, -1);
            $envValue = array_key_exists($key, $env) ? $env[$key] : getenv($key);

            if (is_string($envValue))
                $value[$keys[$i]] = $envValue;
            else throw new RuntimeException($key . ' config key not found in env');
        } else if (preg_match_all('/\${([a-zA-Z_0-9]*)}/', $value[$keys[$i]], $matches))
            for ($j = 0; $j < count($matches[0]); $j++) {
                $envValue = array_key_exists($matches[1][$j], $env) ? $env[$matches[1][$j]] : getenv($matches[1][$j]);

                if (is_string($envValue))
                    $value[$keys[$i]] = str_replace($matches[0][$j], $envValue, $value[$keys[$i]]);
                else throw new RuntimeException($matches[1][$j] . ' config key not found in env');
            }
    }

    return $value;
}

/**
 * load server config
 *
 * @return array
 * @throws RuntimeException
 * @throws JsonException
 */
function loadConfig(): array
{
    if (file_exists(__DIR__ . '/../cache/config.php'))
        return require __DIR__ . '/../cache/config.php';

    $path = __DIR__ . '/../config/config';

    $env = loadEnvFile();

    if (file_exists($path . '.json'))
        $config = loadEnv($env, json_decode(file_get_contents($path . '.json'), true, flags: JSON_THROW_ON_ERROR));
    else if (file_exists($path . '.yml'))
        $config = loadEnv($env, json_decode(json_encode(yaml_parse_file($path . '.yml'), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR));

    if (isset($config)) {
        file_put_contents(__DIR__ . '/../cache/config.php', '<?php return ' . var_export($config, true) . ';');

        return $config;
    }

    throw new RuntimeException('Config not found or can\'t be loaded');
}

function loadBackend($backend, $login = false)
{
    global $config, $db, $redis, $backends;

    if (@$backends[$backend]) {
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
                    return $backends[$backend];
                } else {
                    return false;
                }
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

function loadDomophone($model, $url, $password, $first_time = false)
{
    $path_to_model = __DIR__ . "/../hw/domophones/models/$model";

    if (file_exists($path_to_model)) {
        $class = @json_decode(file_get_contents($path_to_model), true)['class'];

        $directory = new RecursiveDirectoryIterator(__DIR__ . "/../hw/domophones/");
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

/**
 * loads camera class, returns false if .json or class not found
 *
 * @param string $model .json
 * @param string $url
 * @param string $password
 * @param boolean $first_time
 * @return false|object
 */

function loadCamera($model, $url, $password, $first_time = false)
{
    $path_to_model = __DIR__ . "/../hw/cameras/models/$model";

    if (file_exists($path_to_model)) {
        $class = @json_decode(file_get_contents($path_to_model), true)['class'];

        $directory = new RecursiveDirectoryIterator(__DIR__ . "/../hw/cameras/");
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

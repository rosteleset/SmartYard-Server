<?php

function load_env(): array
{
    $content = file_get_contents(path('.env'));
    $lines = explode(PHP_EOL, $content);

    $env = [];

    for ($i = 0; $i < count($lines); $i++) {
        if (str_starts_with($lines[$i], '#')) continue;

        $value = explode('=', $lines[$i], 2);

        if (count($value) == 2)
            $env[$value[0]] = $value[1];
    }

    return $env;
}

function env(?string $key = null): mixed
{
    if (file_exists(path('var/cache/env.php')))
        $env = require path('var/cache/env.php');
    else if (file_exists(path('.env'))) {
        $env = load_env();
    } else throw new RuntimeException('Env not found or can\'t be loaded');

    if ($key !== null) {
        $realEnv = getenv($key);

        if ($realEnv !== false)
            return $realEnv;

        if (array_key_exists($key, $env))
            return $env[$key];

        return null;
    }

    return $env;
}

function load_config(?array $value = null): array
{
    if ($value === null)
        try {
            $value = json_decode(file_get_contents(path('config/config.json')), true, flags: JSON_THROW_ON_ERROR);
        } catch (Exception) {
            $value = [];
        }

    $keys = array_keys($value);
    $matches = [];

    for ($i = 0; $i < count($keys); $i++) {
        if (is_array($value[$keys[$i]]))
            $value[$keys[$i]] = load_config($value[$keys[$i]]);
        else if (str_starts_with($value[$keys[$i]], '${') && str_ends_with($value[$keys[$i]], '}')) {
            $key = substr($value[$keys[$i]], 2, -1);
            $envValue = env($key);

            if ($envValue !== false)
                $value[$keys[$i]] = $envValue;
            else throw new RuntimeException($key . ' config key not found in env');
        } else if (preg_match_all('/\${([a-zA-Z_0-9]*)}/', $value[$keys[$i]], $matches))
            for ($j = 0; $j < count($matches[0]); $j++) {
                $envValue = env($matches[1][$j]);

                if (is_string($envValue))
                    $value[$keys[$i]] = str_replace($matches[0][$j], $envValue, $value[$keys[$i]]);
                else throw new RuntimeException($matches[1][$j] . ' config key not found in env');
            }
    }

    return $value;
}

function config(?string $key = null): mixed
{
    if (file_exists(path('var/cache/config.php')))
        $config = require path('var/cache/config.php');
    else if (file_exists(path('config/config.json'))) {
        $config = load_config();
    } else throw new RuntimeException('Config not found or can\'t be loaded');

    if ($key != null) {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);

            $i = 0;
            $result = $config[$keys[$i++]] ?? null;

            while (isset($result) && $i < count($keys))
                $result = is_array($result) ? @$result[$keys[$i++]] : null;

            if ($i == count($keys))
                return $result;

            return null;
        }

        return $config[$key] ?? null;
    }

    return $config;
}
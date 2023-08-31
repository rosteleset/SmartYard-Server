<?php

use Psr\Log\LoggerInterface;
use Selpol\Logger\FileLogger;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorException;
use Selpol\Validator\ValidatorMessage;

$lastError = false;

if (!function_exists('path')) {
    function path(string $value): string
    {
        return dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $value;
    }
}

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
    }
}

if (!function_exists('task')) {
    function task(Task $task): TaskContainer
    {
        return new TaskContainer($task);
    }
}

if (!function_exists('validate')) {
    function validate(array $value, array $items): array|ValidatorMessage
    {
        $validator = new Validator($value, $items);

        try {
            return $validator->validate();
        } catch (ValidatorException $e) {
            return $e->getValidatorMessage();
        }
    }
}

if (!function_exists('parse_uri')) {
    function parse_uri(string $value): array
    {
        $parts = explode(':', $value);

        $scheme = explode('.', $parts[0]);
        $uri_parts['scheme'] = $scheme[0];

        if (isset($scheme[1])) $uri_parts['transport'] = $scheme[1];
        if (isset($parts[1])) $uri_parts['host'] = $parts[1];
        if (isset($parts[2])) $uri_parts['port'] = $parts[2];

        return $uri_parts;
    }
}

if (!function_exists('request_headers')) {
    function request_headers(): array
    {
        $arh = array();

        $rx_http = '/\AHTTP_/';

        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = explode('_', $arh_key);

                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val)
                        $rx_matches[$ak_key] = ucfirst($ak_val);

                    $arh_key = implode('-', $rx_matches);
                }

                $arh[$arh_key] = $val;
            }
        }

        return ($arh);
    }
}

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }

        return NULL;
    }
}

if (!function_exists('generate_password')) {
    function generate_password($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}

if (!function_exists('check_int')) {
    function check_int(&$int): bool
    {
        $int = trim($int);
        $_int = strval((int)$int);

        if ($int != $_int)
            return false;
        else {
            $int = (int)$_int;
            return true;
        }
    }
}

if (!function_exists('check_string')) {
    function check_string(&$str, array $options = []): bool
    {
        $str = trim($str);

        if (array_key_exists("validChars", $options)) {
            $t = "";

            for ($i = 0; $i < mb_strlen($str); $i++)
                if (in_array(mb_substr($str, $i, 1), $options["validChars"]))
                    $t .= mb_substr($str, $i, 1);

            $str = $t;
        }

        if (!in_array("dontStrip", $options))
            $str = preg_replace('/\s+/', ' ', $str);

        if (array_key_exists("minLength", $options) && mb_strlen($str) < $options["minLength"])
            return false;

        if (array_key_exists("maxLength", $options) && mb_strlen($str) > $options["maxLength"])
            return false;

        return true;
    }
}

if (!function_exists('clear_cache')) {
    function clear_cache($uid): int
    {
        global $redis;

        $keys = [];

        if ($uid === true)
            $keys = $redis->keys("cache_*");
        else if (check_int($uid))
            $keys = $redis->keys("cache_*_$uid");

        $n = 0;

        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }

        return $n;
    }
}

if (!function_exists('last_error')) {
    function last_error(string|bool|null $error = null): string|bool
    {
        global $lastError;

        if (!is_null($error))
            $lastError = $error;

        return $lastError;
    }
}

if (!function_exists('i18n')) {
    function i18n($msg, ...$args)
    {
        global $config;

        $lang = @$config["language"] ?: "ru";

        try {
            $lang = json_decode(file_get_contents(__DIR__ . "/../i18n/$lang.json"), true);
        } catch (Exception) {
            $lang = [];
        }

        try {
            $t = explode(".", $msg);

            if (count($t) > 2) {
                $st = [];
                $st[0] = array_shift($t);
                $st[1] = implode(".", $t);
                $t = $st;
            }

            if (count($t) === 2) $loc = $lang[$t[0]][$t[1]];
            else $loc = $lang[$t[0]];

            if ($loc) {
                if (is_array($loc) && !($loc !== array_values($loc)))
                    $loc = nl2br(implode("\n", $loc));

                $loc = sprintf($loc, ...$args);
            }

            if (!$loc)
                return $t[0] === "errors" ? $t[1] : $msg;

            return $loc;
        } catch (Exception) {
            return $msg;
        }
    }
}

if (!function_exists('guid_v4')) {
    function guid_v4(bool $trim = true): string
    {
        // copyright (c) by Dave Pearson (dave at pds-uk dot com)
        // https://www.php.net/manual/ru/function.com-create-guid.php#119168

        if (function_exists('com_create_guid') === true) {
            if ($trim === true) return trim(com_create_guid(), '{}');
            else return com_create_guid();
        } else if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        mt_srand((double)microtime() * 10000);

        $char = strtolower(md5(uniqid(rand(), true)));

        $hyphen = chr(45);                  // "-"

        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"

        return $lbrace .
            substr($char, 0, 8) . $hyphen .
            substr($char, 8, 4) . $hyphen .
            substr($char, 12, 4) . $hyphen .
            substr($char, 16, 4) . $hyphen .
            substr($char, 20, 12) .
            $rbrace;
    }
}
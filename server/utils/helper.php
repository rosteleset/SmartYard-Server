<?php

use backends\backend;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Container\Container;
use Selpol\Kernel\Kernel;
use Selpol\Logger\FileLogger;
use Selpol\Service\BackendService;
use Selpol\Task\Task;
use Selpol\Task\TaskContainer;
use Selpol\Validator\Validator;
use Selpol\Validator\ValidatorException;
use Selpol\Validator\ValidatorMessage;

$lastError = false;

if (!function_exists('path')) {
    function path(string $value): string
    {
        return dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . $value;
    }
}

if (!function_exists('logger')) {
    function logger(string $channel): LoggerInterface
    {
        return FileLogger::channel($channel);
    }
}

if (!function_exists('kernel')) {
    function kernel(): ?Kernel
    {
        return Kernel::instance();
    }
}

if (!function_exists('container')) {
    /**
     * @template T
     * @psalm-param class-string<T> $key
     * @return T
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    function container(string $key): mixed
    {
        $container = kernel()?->getContainer() ?? Container::instance();

        return $container->get($key);
    }
}

if (!function_exists('backend')) {
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function backend(string $backend, bool $login = false): backend|false
    {
        return container(BackendService::class)->get($backend, $login);
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

        if ($keys > 0)
            $redis->del($keys);

        return count($keys);
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
<?php

namespace Selpol\Cache;

use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Throwable;

class FileCache implements CacheInterface
{
    private array $files = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $this->files)) {
            if (!file_exists(path('var/cache/' . $key . '.php')))
                return $default;

            $this->files[$key] = require_once path('var/cache/' . $key . '.php');
        }

        return $this->files[$key];
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $this->files[$key] = $value;

        if (file_exists(path('var/cache/' . $key . '.php')))
            unlink(path('var/cache/' . $key . '.php'));

        return file_put_contents(path('var/cache/' . $key . '.php'), '<?php return ' . $this->export($value) . ';');
    }

    public function delete(string $key): bool
    {
        if (array_key_exists($key, $this->files))
            unset($this->files[$key]);

        if (file_exists(path('var/cache/' . $key . '.php')))
            unlink(path('var/cache/' . $key . '.php'));

        return true;
    }

    public function clear(): bool
    {
        $files = scandir(path('var/cache/'));

        if ($files === false)
            return false;

        foreach ($files as $file) {
            if (str_ends_with($file, '.php'))
                try {
                    $this->delete(substr($file, 0, -4));
                } catch (Throwable) {
                    return false;
                }
        }

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key)
            yield $this->get($key, $default);
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value)
            $this->set($key, $value);

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key)
            $this->delete($key);

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->files) || file_exists(path('var/cache/' . $key . '.php'));
    }

    private function export(mixed $value): string
    {
        $export = var_export($value, true);

        $patterns = [
            "/array \(/" => '[',
            "/^([ ]*)\)(,?)$/m" => '$1]$2',
            "/=>[ ]?\n[ ]+\[/" => '=> [',
            "/([ ]*)(\'[^\']+\') => ([\[\'])/" => '$1$2 => $3',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $export);
    }
}
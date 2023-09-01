<?php

namespace Selpol\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;
use Selpol\Service\RedisService;

class RedisCache implements CacheInterface
{
    private RedisService $service;

    public function __construct(RedisService $service)
    {
        $this->service = $service;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->service->getRedis()->get('cache:' . $key);

        if ($value === false)
            return $default;

        return $value;
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $timeout = $now->add($ttl);

            return $this->service->getRedis()->set('cache:' . $key, $value, $timeout->getTimestamp() - $now->getTimestamp());
        }

        return $this->service->getRedis()->set('cache:' . $key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->service->getRedis()->del('cache:' . $key) === 1;
    }

    public function clear(): bool
    {
        $keys = $this->service->getRedis()->keys('cache:*');

        if (count($keys) > 0)
            $this->service->getRedis()->del($keys) > 0;

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
            $this->set($key, $value, $ttl);

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
        return $this->service->getRedis()->exists($key) !== false;
    }
}
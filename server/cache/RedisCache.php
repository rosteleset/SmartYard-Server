<?php

namespace Selpol\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use RedisException;
use Selpol\Container\Container;
use Selpol\Service\RedisService;

class RedisCache implements CacheInterface
{
    private RedisService $service;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Container $container)
    {
        $this->service = $container->get(RedisService::class);
    }

    /**
     * @throws RedisException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->service->getRedis()->get('cache:' . $key);

        if ($value === false)
            return $default;

        return $value;
    }

    /**
     * @throws RedisException
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if ($ttl instanceof DateInterval) {
            $now = new DateTimeImmutable();
            $timeout = $now->add($ttl);

            return $this->service->getRedis()->set('cache:' . $key, $value, $timeout->getTimestamp() - $now->getTimestamp());
        }

        return $this->service->getRedis()->set('cache:' . $key, $value, $ttl);
    }

    /**
     * @throws RedisException
     */
    public function delete(string $key): bool
    {
        return $this->service->getRedis()->del('cache:' . $key) === 1;
    }

    /**
     * @throws RedisException
     */
    public function clear(): bool
    {
        $keys = $this->service->getRedis()->keys('cache:*');

        if (count($keys) > 0)
            $this->service->getRedis()->del($keys) > 0;

        return true;
    }

    /**
     * @throws RedisException
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key)
            yield $this->get($key, $default);
    }

    /**
     * @throws RedisException
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value)
            $this->set($key, $value, $ttl);

        return true;
    }

    /**
     * @throws RedisException
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key)
            $this->delete($key);

        return true;
    }

    /**
     * @throws RedisException
     */
    public function has(string $key): bool
    {
        return $this->service->getRedis()->exists($key) !== false;
    }
}
<?php

namespace Selpol\Container;

use Exception;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private static ?Container $container = null;

    private array $instances = [];
    private array $factories = [];

    public function __construct(bool $configure = true)
    {
        if ($configure) {
            if (file_exists(path('var/cache/container.php')))
                $this->factories = require_once path('var/cache/container.php');
            else if (file_exists(path('config/container.php'))) {
                $callback = require_once path('config/container.php');
                $builder = new ContainerBuilder();

                $callback($builder);

                $this->factories = $builder->getFactories();
            }
        }
    }

    public function singleton(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [true, $factory];
    }

    public function factory(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [false, $factory];
    }

    public function set(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * @template T
     * @psalm-param class-string<T> $id
     * @param string $id
     * @return T
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances))
            return $this->instances[$id];

        if (array_key_exists($id, $this->factories)) {
            $class = $this->factories[$id][1] ?? $id;

            if (!class_exists($class))
                throw new ContainerNotFoundException($this, $id, $id . ' not found');

            $instance = new $class($this);

            if ($this->factories[$id][0])
                $this->instances[$id] = $instance;

            return $instance;
        }

        throw new ContainerNotFoundException($this, $id, $id . ' not found');
    }

    /**
     * @template T
     * @psalm-param class-string<T> $id
     * @psalm-param bool $singleton
     * @return T
     */
    public function make(string $id, bool $singleton = false): mixed
    {
        if (array_key_exists($id, $this->instances))
            return $this->instances[$id];

        if (!class_exists($id))
            throw new ContainerNotFoundException($this, $id, $id . ' not found');

        $instance = new $id($this);

        if ($singleton)
            $this->instances[$id] = $instance;

        return $instance;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->factories);
    }

    public function dispose(): void
    {
        foreach ($this->instances as $instance) {
            if ($instance instanceof ContainerDispose)
                try {
                    $instance->dispose();
                } catch (Exception $exception) {
                    logger('container')->error($exception);
                }
        }
    }

    // TODO: Потом полностью удалить статический доступ к классу
    public static function instance(): Container
    {
        if (self::$container === null)
            self::$container = new Container();

        return self::$container;
    }
}
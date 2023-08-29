<?php

namespace Selpol\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private static ?Container $container = null;

    private array $instances = [];
    private array $factories = [];

    public function singleton(string $id, callable $factory)
    {
        $this->factories[$id] = [true, $factory];
    }

    public function factory(string $id, callable $factory)
    {
        $this->factories[$id] = [false, $factory];
    }

    public function get(string $id)
    {
        if (@$this->instances[$id])
            return $this->instances[$id];

        if (@$this->factories[$id]) {
            $factory = $this->factories[$id];

            $instance = count(func_get_args()) == 1 ? call_user_func($factory[1], $this) : call_user_func($factory[1]);

            if ($factory[0])
                $this->instances[$id] = $instance;

            return $instance;
        }

        throw new ContainerNotFoundException($this, $id, $id . ' not found');
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->instances) || array_key_exists($id, $this->factories);
    }

    // TODO: Потом удалить полностью статический доступ к классу
    public static function instance(): Container
    {
        if (self::$container === null)
            self::$container = new Container();

        return self::$container;
    }
}
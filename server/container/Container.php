<?php

namespace Selpol\Container;

use Exception;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private static ?Container $container = null;

    private array $instances = [];
    private array $factories = [];

    public function singleton(string $id, ContainerFactory|callable $factory)
    {
        $this->factories[$id] = [true, $factory];
    }

    public function factory(string $id, ContainerFactory|callable $factory)
    {
        $this->factories[$id] = [false, $factory];
    }

    public function set(string $id, mixed $value)
    {
        $this->instances[$id] = $value;
    }

    public function get(string $id)
    {
        if (array_key_exists($id, $this->instances))
            return $this->instances[$id];

        if (array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            $callback = $factory[1];

            $instance = $callback instanceof ContainerFactory ? $callback->__invoke($this) : call_user_func($callback, $this);

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

    public function dispose()
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

    public static function file(string $path): Container
    {
        if (file_exists($path)) {
            $callable = require_once $path;

            if (is_callable($callable)) {
                $container = self::instance();

                $callable($container);

                return $container;
            }
        }

        throw new ContainerException(null, 'File "' . $path . '" not exist');
    }

    // TODO: Потом полностью удалить статический доступ к классу
    public static function instance(): Container
    {
        if (self::$container === null)
            self::$container = new Container();

        return self::$container;
    }
}
<?php

namespace Selpol\Router;

use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private array $routes = [];

    /** @var string[] $middlewares */
    private array $middlewares = [];

    public function __construct(bool $configure = true)
    {
        if ($configure) {
            if (file_exists(path('var/cache/router.php'))) {
                $router = require_once path('var/cache/router.php');

                $this->routes = $router['routes'];
                $this->middlewares = $router['middlewares'];
            } else if (file_exists(path('config/router.php'))) {
                $callback = require_once path('config/router.php');
                $builder = new RouterBuilder();

                $callback($builder);

                $this->routes = $builder->getRoutes();
                $this->middlewares = $builder->getMiddlewares();
            }
        }
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function bootstrap(string $value): static
    {
        if (file_exists(path('var/cache/router-' . $value . '.php')))
            $this->routes = require_once path('var/cache/' . $value . '.php');
        else if (file_exists(path('config/router/' . $value . '.php'))) {
            $callback = require_once path('config/router/' . $value . '.php');

            $callback($this);
        }

        return $this;
    }

    public function match(ServerRequestInterface $request): ?array
    {
        $path = $request->getUri()->getPath();
        $segments = array_map(static fn(string $segment) => '/' . $segment, array_filter(explode('/', $path), static fn(string $segment) => $segment !== ''));

        if (array_key_exists($segments[1], $this->routes)) {
            $route = array_reduce(array_slice($segments, 1), static function (?array $previous, string $current) {
                if ($previous === null || !array_key_exists($current, $previous['routes']))
                    return null;

                return $previous['routes'][$current];
            }, $this->routes[$segments[1]]);

            if ($route && array_key_exists('type', $route) && $route['type'] === $request->getMethod())
                return $route;
        }

        return null;
    }
}
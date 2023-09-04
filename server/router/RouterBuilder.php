<?php

namespace Selpol\Router;

use stdClass;

class RouterBuilder
{
    private array $routes = [];
    private array $middlewares = [];

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function group(string $path, callable $callback): static
    {
        $builder = new RouterBuilder();
        $callback($builder);

        $routes = $builder->getRoutes();

        $this->applyMiddlewares($routes, $builder->getMiddlewares());

        $this->routes[$path] = $routes;

        return $this;
    }

    public function get(string $route, string $class, string $method = '__invoke', array $middlewares = []): static
    {
        return $this->route('GET', $route, $class, $method, $middlewares);
    }

    public function post(string $route, string $class, string $method = '__invoke', array $middlewares = []): static
    {
        return $this->route('POST', $route, $class, $method, $middlewares);
    }

    public function put(string $route, string $class, string $method = '__invoke', array $middlewares = []): static
    {
        return $this->route('PUT', $route, $class, $method, $middlewares);
    }

    public function delete(string $route, string $class, string $method = '__invoke', array $middlewares = []): static
    {
        return $this->route('DELETE', $route, $class, $method, $middlewares);
    }

    public function middleware(string $value): static
    {
        $this->middlewares[] = $value;

        return $this;
    }

    private function route(string $type, string $route, string $class, string $method = '__invoke', array $middlewares = []): static
    {
        $segments = array_map(static fn(string $segment) => '/' . $segment, array_filter(explode('/', $route), static fn(string $segment) => $segment !== ''));

        $routes = &$this->routes;

        for ($i = 1; $i < count($segments); $i++) {
            if (!array_key_exists($segments[$i], $routes))
                $routes[$segments[$i]] = [];

            $routes = &$routes[$segments[$i]];
        }

        $routes[$segments[count($segments)]] = ['type' => $type, 'class' => $class, 'method' => $method, 'middlewares' => $middlewares];

        return $this;
    }

    private function applyMiddlewares(array &$route, array $middlewares): void
    {
        if (array_key_exists('middlewares', $route))
            $route['middlewares'] = $route['middlewares'] + $middlewares;
        else foreach ($route as &$childRoute) $this->applyMiddlewares($childRoute, $middlewares);
    }
}
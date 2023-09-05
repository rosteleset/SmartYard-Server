<?php

namespace Selpol\Router;

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

        foreach ($routes as $type => &$childRoutes) {
            $this->applyMiddlewares($childRoutes, $builder->getMiddlewares());

            if (!array_key_exists($type, $this->routes))
                $this->routes[$type] = [];

            $this->routes[$type][$path] = $routes[$type];
        }

        return $this;
    }

    public function get(string $route, array|string $class, array $middlewares = []): static
    {
        return $this->route('GET', $route, $class, $middlewares);
    }

    public function post(string $route, array|string $class, array $middlewares = []): static
    {
        return $this->route('POST', $route, $class, $middlewares);
    }

    public function put(string $route, array|string $class, array $middlewares = []): static
    {
        return $this->route('PUT', $route, $class, $middlewares);
    }

    public function delete(string $route, array|string $class, array $middlewares = []): static
    {
        return $this->route('DELETE', $route, $class, $middlewares);
    }

    public function middleware(string $value): static
    {
        $this->middlewares[] = $value;

        return $this;
    }

    private function route(string $type, string $route, array|string $class, array $middlewares = []): static
    {
        $segments = array_map(static fn(string $segment) => '/' . $segment, array_filter(explode('/', $route), static fn(string $segment) => $segment !== ''));

        if (!array_key_exists($type, $this->routes))
            $this->routes[$type] = [];

        $routes = &$this->routes[$type];

        for ($i = 1; $i < count($segments); $i++) {
            if (!array_key_exists($segments[$i], $routes))
                $routes[$segments[$i]] = [];

            $routes = &$routes[$segments[$i]];
        }

        $method = is_array($class) ? (array_key_exists(1, $class) ? $class[1] : '__invoke') : '__invoke';

        if (count($segments) === 0)
            $routes = ['class' => is_array($class) ? $class[0] : $class, 'method' => $method, 'middlewares' => $middlewares];
        else
            $routes[$segments[count($segments)]] = ['class' => is_array($class) ? $class[0] : $class, 'method' => $method, 'middlewares' => $middlewares];

        return $this;
    }

    private function applyMiddlewares(array &$route, array $middlewares): void
    {
        if (array_key_exists('middlewares', $route))
            $route['middlewares'] = $route['middlewares'] + $middlewares;
        else foreach ($route as &$childRoute) $this->applyMiddlewares($childRoute, $middlewares);
    }
}
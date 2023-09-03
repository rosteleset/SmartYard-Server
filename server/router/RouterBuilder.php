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
        $middlewares = $builder->getMiddlewares();

        foreach ($routes as &$route)
            $this->applyMiddlewares($route, $middlewares);

        $this->routes[$path] = ['routes' => $routes];

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
        $this->routes[$route] = ['type' => $type, 'class' => $class, 'method' => $method, 'middlewares' => $middlewares];

        return $this;
    }

    private function applyMiddlewares(array &$route, array $middlewares): void
    {
        if (array_key_exists('middlewares', $route))
            $route['middlewares'] = $route['middlewares'] + $middlewares;
        else foreach ($route['routes'] as &$childRoute)
            $this->applyMiddlewares($childRoute, $middlewares);
    }
}
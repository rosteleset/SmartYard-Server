<?php

namespace Selpol\Router;

class RouterBuilder
{
    private array $routes = [];

    private array $includes = [];
    private array $excludes = [];

    public function collect(bool $transform = true): array
    {
        $routes = $this->routes;

        foreach ($routes as &$childRoutes)
            $this->applyMiddlewares($childRoutes);

        if ($transform)
            foreach ($routes as &$childRoutes)
                $this->applyTransform($childRoutes);

        return $routes;
    }

    public function group(string $path, callable $callback): static
    {
        $builder = new RouterBuilder();
        $callback($builder);

        $routes = $builder->collect(false);

        foreach ($routes as $type => $childRoutes) {
            if (!array_key_exists($type, $this->routes))
                $this->routes[$type] = [];

            $this->routes[$type][$path] = $childRoutes;
        }

        return $this;
    }

    public function get(string $route, array|string $class, array $middlewares = [], array $excludes = []): static
    {
        return $this->route('GET', $route, $class, $middlewares, $excludes);
    }

    public function post(string $route, array|string $class, array $middlewares = [], array $excludes = []): static
    {
        return $this->route('POST', $route, $class, $middlewares, $excludes);
    }

    public function put(string $route, array|string $class, array $middlewares = [], array $excludes = []): static
    {
        return $this->route('PUT', $route, $class, $middlewares, $excludes);
    }

    public function delete(string $route, array|string $class, array $middlewares = [], array $excludes = []): static
    {
        return $this->route('DELETE', $route, $class, $middlewares, $excludes);
    }

    public function include(string $value): static
    {
        $this->includes[] = $value;

        return $this;
    }

    public function exclude(string $value): static
    {
        $this->excludes[] = $value;

        return $this;
    }

    private function route(string $type, string $route, array|string $class, array $includes = [], array $excludes = []): static
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
            $routes = ['class' => is_array($class) ? $class[0] : $class, 'method' => $method, 'includes' => $includes, 'excludes' => $excludes];
        else
            $routes[$segments[count($segments)]] = ['class' => is_array($class) ? $class[0] : $class, 'method' => $method, 'includes' => $includes, 'excludes' => $excludes];

        return $this;
    }

    private function applyMiddlewares(array &$route): void
    {
        if (array_key_exists('includes', $route) && array_key_exists('excludes', $route)) {
            $route['includes'] = $route['includes'] + $this->includes;
            $route['excludes'] = $route['excludes'] + $this->excludes;
        } else foreach ($route as &$childRoute) $this->applyMiddlewares($childRoute);
    }

    private function applyTransform(array &$route): void
    {
        if (array_key_exists('includes', $route) && array_key_exists('excludes', $route)) {
            $route['middlewares'] = array_filter($route['includes'], static fn(string $value) => !in_array($value, $route['excludes']));

            unset($route['includes']);
            unset($route['excludes']);
        } else foreach ($route as &$childRoute) $this->applyTransform($childRoute);
    }
}
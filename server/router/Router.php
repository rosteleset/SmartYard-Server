<?php

namespace Selpol\Router;

use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private array $routes = [];

    public function __construct(bool $configure = true)
    {
        if ($configure) {
            if (file_exists(path('var/cache/router.php'))) {
                $router = require_once path('var/cache/router.php');

                $this->routes = $router;
            } else if (file_exists(path('config/router.php'))) {
                $callback = require_once path('config/router.php');
                $builder = new RouterBuilder();

                $callback($builder);

                $this->routes = $builder->collect();
            }
        }
    }

    public function match(ServerRequestInterface $request): ?RouterMatch
    {
        $method = $request->getMethod();

        if (!array_key_exists($method, $this->routes))
            return null;

        $path = $request->getUri()->getPath();
        $segments = array_map(static fn(string $segment) => '/' . $segment, array_filter(explode('/', $path), static fn(string $segment) => $segment !== ''));

        $params = [];

        $routes = $this->routes[$method];

        for ($i = 1; $i <= count($segments); $i++) {
            if (array_key_exists($segments[$i], $routes))
                $routes = $routes[$segments[$i]];
            else {
                $find = false;

                foreach ($routes as $key => $route) {
                    if (str_starts_with($key, '/{') && str_ends_with($key, '}')) {
                        $params[substr($key, 2, -1)] = substr($segments[$i], 1);

                        $routes = $route;

                        $find = true;

                        break;
                    }
                }

                if (!$find)
                    $routes = null;
            }
        }

        if ($routes && array_key_exists('class', $routes) && array_key_exists('method', $routes) && array_key_exists('middlewares', $routes))
            return new RouterMatch($routes['class'], $routes['method'], $params, $routes['middlewares']);

        return null;
    }
}
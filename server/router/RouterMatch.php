<?php

namespace Selpol\Router;

use Exception;
use Selpol\Validator\Rule;

class RouterMatch
{
    private string $class;
    private string $method;

    private array $params;

    private array $middlewares;

    public function __construct(string $class, string $method, array $params, array $middlewares)
    {
        $this->class = $class;
        $this->method = $method;

        $this->params = $params;

        $this->middlewares = $middlewares;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getParam(string $key): ?string
    {
        return array_key_exists($key, $this->params) ? $this->params[$key] : null;
    }

    public function getParamInt(string $key): ?int
    {
        try {
            return Rule::int()->onItem($key, $this->params);
        } catch (Exception) {
            return null;
        }
    }
}
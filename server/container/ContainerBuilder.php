<?php

namespace Selpol\Container;

class ContainerBuilder
{
    private array $factories = [];

    public function getFactories(): array
    {
        return $this->factories;
    }

    public function singleton(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [true, $factory];
    }

    public function factory(string $id, ?string $factory = null): void
    {
        $this->factories[$id] = [false, $factory];
    }
}
<?php

namespace Selpol\Container;

use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    private string $id;

    public function __construct(Container $container, string $id, $message = "")
    {
        parent::__construct($container, $message);

        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
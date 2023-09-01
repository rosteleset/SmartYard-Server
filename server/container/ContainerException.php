<?php

namespace Selpol\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
    private Container $container;

    public function __construct(Container $container, $message = "")
    {
        parent::__construct($message);

        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
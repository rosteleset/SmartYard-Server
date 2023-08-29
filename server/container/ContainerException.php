<?php

namespace Selpol\Container;

use Exception;
use Psr\Container\ContainerExceptionInterface;

class ContainerException extends Exception implements ContainerExceptionInterface
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
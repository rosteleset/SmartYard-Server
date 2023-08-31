<?php

namespace Selpol\Task;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

abstract class Task implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public string $title;

    protected ContainerInterface $container;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public abstract function onTask(): mixed;

    public function onError(Throwable $throwable)
    {

    }

    protected function setProgress(int|float $progress)
    {

    }
}
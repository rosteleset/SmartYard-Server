<?php

namespace Selpol\Task;

use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

abstract class Task implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public string $title;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public abstract function onTask(): mixed;

    public function onError(Throwable $throwable): void
    {

    }

    protected function setProgress(int|float $progress): void
    {

    }
}
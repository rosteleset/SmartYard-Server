<?php

namespace Selpol\Task;

use Exception;
use PDO_EXT;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Redis;
use Throwable;

abstract class Task implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public string $title;

    protected ?Redis $redis = null;
    protected ?PDO_EXT $pdo = null;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public function setRedis(?Redis $redis): void
    {
        $this->redis = $redis;
    }

    public function setPdo(?PDO_EXT $pdo): void
    {
        $this->pdo = $pdo;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public abstract function onTask(): mixed;

    public function onError(Throwable $throwable)
    {

    }

    protected function setProgress(int $progress)
    {

    }
}
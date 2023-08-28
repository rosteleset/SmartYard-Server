<?php

namespace Selpol\Task;

use PDO_EXT;
use Redis;
use Throwable;

abstract class Task
{
    public string $title;

    protected ?Redis $redis;
    protected ?PDO_EXT $pdo;
    protected ?array $config;

    /** @var callable $progressCallable */
    private mixed $progressCallable;

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

    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }

    public function setProgressCallable(?callable $progressCallable)
    {
        $this->progressCallable = $progressCallable;
    }

    public abstract function onTask();

    public function onError(Throwable $throwable)
    {

    }

    protected function setProgress(int $progress)
    {
        if ($this->progressCallable)
            call_user_func($this->progressCallable, $progress);
    }
}
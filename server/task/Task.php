<?php

namespace Selpol\Task;

use Exception;
use PDO_EXT;
use Redis;
use Throwable;

abstract class Task
{
    public string $title;

    protected ?Redis $redis = null;
    protected ?PDO_EXT $pdo = null;
    protected ?array $config = null;

    /** @var callable $progressCallable */
    private mixed $progressCallable = null;

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
        if ($this->progressCallable)
            call_user_func($this->progressCallable, $progress);
    }
}
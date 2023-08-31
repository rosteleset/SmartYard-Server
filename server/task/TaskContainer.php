<?php

namespace Selpol\Task;

use Exception;
use PDO_EXT;
use Redis;

class TaskContainer
{
    private Task $task;

    private ?string $queue = null;
    private ?int $start = null;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function queue(?string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function high(): static
    {
        return $this->queue(TaskManager::QUEUE_HIGH);
    }

    public function medium(): static
    {
        return $this->queue(TaskManager::QUEUE_MEDIUM);
    }

    public function low(): static
    {
        return $this->queue(TaskManager::QUEUE_LOW);
    }

    public function default(): static
    {
        return $this->queue(TaskManager::QUEUE_DEFAULT);
    }

    public function delay(?int $start): static
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function sync(?Redis $redis, ?PDO_EXT $pdo): mixed
    {
        $this->task->setRedis($redis);
        $this->task->setPdo($pdo);

        return $this->task->onTask();
    }

    public function dispatch(): bool
    {
        $queue = $this->queue ?? TaskManager::QUEUE_DEFAULT;
        $start = $this->start;

        try {
            TaskManager::instance()->enqueue($queue, $this->task, $start);

            return true;
        } catch (Exception $exception) {
            logger('task')->error($exception);

            return false;
        } finally {
            TaskManager::instance()->close();
        }
    }
}
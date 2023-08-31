<?php

namespace Selpol\Task;

use Exception;
use Selpol\Service\TaskService;

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
        return $this->queue(TaskService::QUEUE_HIGH);
    }

    public function medium(): static
    {
        return $this->queue(TaskService::QUEUE_MEDIUM);
    }

    public function low(): static
    {
        return $this->queue(TaskService::QUEUE_LOW);
    }

    public function default(): static
    {
        return $this->queue(TaskService::QUEUE_DEFAULT);
    }

    public function delay(?int $start): static
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function sync(): mixed
    {
        $this->task->setContainer(bootstrap_if_need());

        return $this->task->onTask();
    }

    public function dispatch(): bool
    {
        $logger = logger('task');
        $queue = $this->queue ?? TaskService::QUEUE_DEFAULT;

        try {
            $manager = TaskService::instance();
            $manager->setLogger($logger);

            $manager->enqueue($queue, $this->task, $this->start);

            return true;
        } catch (Exception $exception) {
            $logger->error($exception);

            return false;
        } finally {
            TaskService::instance()->close();
        }
    }
}
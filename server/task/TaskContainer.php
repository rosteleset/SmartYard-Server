<?php

namespace Selpol\Task;

use DateInterval;
use DateTime;

class TaskContainer
{
    private Task $task;

    private ?string $queue = null;
    private DateTime|DateInterval|int|null $start = null;

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

    public function start(?DateTime $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function delay(DateInterval|int|null $start): static
    {
        $this->start = $start;

        return $this;
    }

    public function dispatch(): bool
    {
        $queue = $this->queue ?? TaskManager::QUEUE_DEFAULT;
        $start = is_null($this->start) ? time() : ($this->start instanceof DateInterval ? ((new DateTime())->add($this->start)->getTimestamp()) : $this->start->getTimestamp());

        TaskManager::instance()->worker($queue)->pushTask($start, $this->task);

        return true;
    }
}
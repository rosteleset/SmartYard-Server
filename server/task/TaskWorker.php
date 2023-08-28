<?php

namespace Selpol\Task;

use Selpol\Logger\Logger;
use Redis;
use Throwable;

class TaskWorker
{
    private string $queue;

    private Redis $redis;
    private ?Logger $logger;

    public function __construct(string $queue, Redis $redis, ?Logger $logger)
    {
        $this->queue = $queue;
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function getLogger(): ?Logger
    {
        return $this->logger;
    }

    public function getTitle(int $id): ?string
    {
        $title = $this->redis->get($this->getWorkerTitleKey($id));

        return $title === false ? null : $title;
    }

    public function getProgress(int $id): ?int
    {
        $progress = $this->redis->get($this->getWorkerProgressKey($id));

        return $progress === false ? null : $progress;
    }

    public function getIds(): array
    {
        $ids = $this->redis->lRange($this->getWorkerIdsKey(), 0, -1);

        return $ids != false ? $ids : [];
    }

    public function getSize(): int
    {
        $size = $this->redis->get($this->getWorkerSizeKey());

        return $size != false ? $size : 0;
    }

    public function has(int $id): bool
    {
        return in_array($id, $this->getIds());
    }

    public function next(): int
    {
        $id = $this->redis->lIndex($this->getWorkerIdsKey(), 0);

        return $id !== false ? $id + 1 : 1;
    }

    public function start(int $id)
    {
        $this->redis->lPush($this->getWorkerIdsKey(), $id);

        $this->logger?->info('Start TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    public function pushTask(int $start, Task $task)
    {
        $this->rawPush(false, $start, serialize($task));

        $this->logger?->info('Push new Task', ['queue' => $this->queue, 'class' => get_class($this)]);
    }

    public function pushCommand(int $id, string $command)
    {
        $this->redis->lPush($this->getWorkerCommandKey($id), $command);

        $this->logger?->info('Push command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
    }

    public function setTitle(int $id, ?string $title)
    {
        if ($title !== null) $this->redis->set($this->getWorkerTitleKey($id), $title);
        else $this->redis->del($this->getWorkerTitleKey($id));
    }

    public function setProgress(int $id, ?int $progress)
    {
        if ($progress !== null) $this->redis->set($this->getWorkerProgressKey($id), $progress);
        else $this->redis->del($this->getWorkerProgressKey($id));
    }

    public function popTask(): ?Task
    {
        $raw = $this->redis->lPop($this->getWorkerTasksKey());

        if (is_null($raw) || $raw === FALSE)
            return null;

        $this->redis->decr($this->getWorkerSizeKey());

        try {
            $json = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
            $task = unserialize($json['d']);

            if (!$task)
                return null;

            if (time() < $json['s']) {
                $this->rawPush(true, $json['s'], $json['d']);

                return null;
            }

            return $task;
        } catch (Throwable $throwable) {
            $this->logger?->error('Pop task error' . PHP_EOL . $throwable, ['queue' => $this->queue]);

            return null;
        }
    }

    public function popCommand(int $id): ?string
    {
        $command = $this->redis->lPop($this->getWorkerCommandKey($id));

        return $command === false ? null : $command;
    }

    public function stop(int $id)
    {
        $this->redis->lRem($this->getWorkerIdsKey(), $id, 1);

        $this->logger?->info('Stop TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    public function clear()
    {
        $this->redis->del($this->getWorkerTasksKey());
        $this->redis->set($this->getWorkerSizeKey(), 0);

        $this->logger?->info('Clear Taskworker', ['queue' => $this->queue]);
    }

    private function rawPush(bool $r, int $start, string $task)
    {
        if ($r)
            $this->redis->rPush($this->getWorkerTasksKey(), json_encode(['s' => $start, 'd' => $task]));
        else
            $this->redis->lPush($this->getWorkerTasksKey(), json_encode(['s' => $start, 'd' => $task]));

        $this->redis->incr($this->getWorkerSizeKey());
    }

    private function getWorkerTasksKey(): string
    {
        return 'task:' . $this->queue . ':task';
    }

    private function getWorkerCommandKey(int $id): string
    {
        return 'task:' . $this->queue . ':worker:' . $id . ':command';
    }

    private function getWorkerTitleKey(int $id): string
    {
        return 'task:' . $this->queue . ':worker:' . $id . ':title';
    }

    private function getWorkerProgressKey(int $id): string
    {
        return 'task:' . $this->queue . ':worker:' . $id . ':progress';
    }

    private function getWorkerIdsKey(): string
    {
        return 'task:' . $this->queue . ':workers';
    }

    private function getWorkerSizeKey(): string
    {
        return 'task:' . $this->queue . ':size';
    }
}
<?php

namespace tasks;

require_once dirname(__FILE__) . "/IntercomConfigureTask.php";

use DateInterval;
use DateTime;
use Logger;
use PDO_EXT;
use Redis;
use Throwable;

function task(Task $task): TaskContainer
{
    return new TaskContainer($task);
}

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

    public function dispatch()
    {
        $queue = $this->queue ?? TaskManager::QUEUE_DEFAULT;
        $start = is_null($this->start) ? time() : ($this->start instanceof DateInterval ? ((new DateTime())->add($this->start)->getTimestamp()) : $this->start->getTimestamp());

        TaskManager::instance()->worker($queue)->pushTask($start, $this->task);
    }
}

class TaskWorker
{
    private string $queue;

    private Redis $redis;
    private Logger $logger;

    public function __construct(string $queue, Redis $redis, Logger $logger)
    {
        $this->queue = $queue;
        $this->redis = $redis;
        $this->logger = $logger;
    }

    public function getLogger(): Logger
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
        return $this->redis->lRange($this->getWorkerIdsKey(), 0, -1);
    }

    public function getSize(): int
    {
        return $this->redis->get($this->getWorkerSizeKey());
    }

    public function has(int $id): bool
    {
        return in_array($id, $this->getIds());
    }

    public function next(): int
    {
        return ($this->redis->lIndex($this->getWorkerIdsKey(), -1) ?? 0) + 1;
    }

    public function start(int $id)
    {
        $this->redis->lPush($this->getWorkerIdsKey(), $id);

        $this->logger->info('Start TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    public function pushTask(int $start, Task $task)
    {
        $this->rawPush(false, $start, serialize($task));

        $this->logger->info('Push new Task', ['queue' => $this->queue, 'class' => get_class($this)]);
    }

    public function pushCommand(int $id, string $command)
    {
        $this->redis->lPush($this->getWorkerCommandKey($id), $command);

        $this->logger->info('Push command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
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
        return $this->redis->lPop($this->getWorkerCommandKey($id)) ?? null;
    }

    public function stop(int $id)
    {
        $this->redis->lRem($this->getWorkerIdsKey(), $id, 1);

        $this->logger->info('Stop TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    public function clear()
    {
        $this->redis->del($this->getWorkerTasksKey());
        $this->redis->set($this->getWorkerSizeKey(), 0);

        $this->logger->info('Clear Taskworker', ['queue' => $this->queue]);
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
        return 'task:' . $this->queue . ':tasks';
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

class TaskManager
{
    public const QUEUE_HIGH = 'high';
    public const QUEUE_MEDIUM = 'medium';
    public const QUEUE_LOW = 'low';
    public const QUEUE_DEFAULT = 'default';

    private static ?TaskManager $instance = null;

    private Redis $redis;

    /** @var TaskWorker[] $workers */
    private array $workers = [];

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function getQueues(): array
    {
        $queues = $this->redis->sMembers($this->getManagerQueuesKey());

        return is_array($queues) ? $queues : [];
    }

    public function worker(string $queue): TaskWorker
    {
        if (!array_key_exists($queue, $this->workers)) {
            $this->workers[$queue] = new TaskWorker($queue, $this->redis, Logger::channel('task'));

            $this->redis->sAdd($this->getManagerQueuesKey(), $queue);
        }

        return $this->workers[$queue];
    }

    private function getManagerQueuesKey(): string
    {
        return 'task:queues';
    }

    public static function instance(): TaskManager
    {
        global $redis;

        if (is_null(self::$instance))
            self::$instance = new TaskManager($redis);

        return self::$instance;
    }
}
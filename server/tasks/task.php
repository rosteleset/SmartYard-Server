<?php

namespace tasks;

include_once __DIR__ . "./IntercomConfigureTask.php";

use Exception;
use Logger;
use PDO_EXT;
use Redis;

function task(Task $task): TaskContainer
{
    return new TaskContainer($task);
}

abstract class Task
{
    protected ?Redis $redis = null;
    protected ?PDO_EXT $pdo = null;
    protected ?array $config = null;

    protected ?Logger $logger = null;

    public abstract function onTask();

    public function setRedis(?Redis $redis)
    {
        $this->redis = $redis;
    }

    public function setPdo(?PDO_EXT $pdo)
    {
        $this->pdo = $pdo;
    }

    public function setConfig(?array $config)
    {
        $this->config = $config;
    }

    public function setLogger(?Logger $logger)
    {
        $this->logger = $logger;
    }
}

class TaskContainer
{
    private Task $task;

    private ?string $queue = null;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function queue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function dispatch(): bool
    {
        TaskManager::instance()->worker($this->queue ?? 'default')->push($this->task);

        return true;
    }
}

class TaskWorker
{
    private string $queue;

    private Redis $redis;

    private ?Logger $logger = null;

    public function __construct(string $queue, Redis $redis)
    {
        $this->queue = $queue;

        $this->redis = $redis;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    public function getLogger(): ?Logger
    {
        return $this->logger;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Проверяет, существует ли TaskWorker с таким id
     * @param int $id
     * @return bool
     */
    public function has(int $id): bool
    {
        $ids = $this->redis->lRange('task:' . $this->queue . ':worker', 0, -1);

        return in_array($id, $ids);
    }

    public function next(): int
    {
        $id = $this->redis->lIndex('task:' . $this->queue . ':worker', -1);

        return $id + 1;
    }

    /**
     * Добавляем информацию, что TaskWorker запущен
     * @param int $id
     */
    public function start(int $id)
    {
        $this->redis->lPush('task:' . $this->queue . ':worker', $id);

        $this->logger?->info('Start TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    /**
     * Получить команду, адресованную TaskWorker с определенным id
     * @param int $id
     * @return string|null
     */
    public function command(int $id): ?string
    {
        $command = $this->redis->lPop('task:' . $this->queue . ':worker:' . $id);

        return is_string($command) ? $command : null;
    }

    /**
     * Отправить команду TaskWorker
     * @param int|null $id Идентификатор TaskWorker
     * @param string $command
     */
    public function send(?int $id, string $command)
    {
        if (is_null($id)) {
            $ids = $this->redis->lRange('task:' . $this->queue . ':worker', 0, -1);

            foreach ($ids as $id) {
                $this->redis->lPush('task:' . $this->queue . ':worker:' . $id, $command);

                $this->logger?->info('Send command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
            }
        } else if ($this->has($id)) {
            $this->redis->lPush('task:' . $this->queue . ':worker:' . $id, $command);

            $this->logger?->info('Send command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
        }
    }

    /**
     * Добавляем новую зачаду в TaskWorker
     * Задача добавляется в начало очереди
     * @param Task $task
     */
    public function push(Task $task)
    {
        $this->redis->lPush('task:' . $this->queue . ':tasks', serialize($task));

        $this->logger?->info('Push new Task', ['queue' => $this->queue, 'class' => get_class($this)]);
    }

    /**
     * Вытащить и вернуть задачу
     * @return Task|null
     */
    public function pop(): ?Task
    {
        $raw = $this->redis->lPop('task:' . $this->queue . ':tasks');

        if (is_null($raw))
            return null;

        try {
            $task = unserialize($raw);

            if (!$task)
                return null;

            return $task;
        } catch (Exception $e) {
            $this->logger?->error('Unserializable error' . PHP_EOL . $e, ['queue' => $this->queue]);

            return null;
        }
    }

    /**
     * Убираем информацию, что TaskWorker запущен
     * @param int $id
     */
    public function stop(int $id)
    {
        $this->redis->lRem('task:' . $this->queue . ':worker', $id, 1);

        $this->logger?->info('Stop TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }
}

class TaskManager
{
    private static ?TaskManager $instance = null;

    private Redis $redis;

    /** @var TaskWorker[] $workers */
    private array $workers = [];

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Если TaskWorker не существует, он его создает
     * @param string $queue
     * @return TaskWorker
     */
    public function worker(string $queue): TaskWorker
    {
        if (!array_key_exists($queue, $this->workers))
            $this->workers[$queue] = new TaskWorker($queue, $this->redis);

        return $this->workers[$queue];
    }

    public static function instance(): TaskManager
    {
        global $redis;

        if (is_null(self::$instance))
            self::$instance = new TaskManager($redis);

        return self::$instance;
    }
}

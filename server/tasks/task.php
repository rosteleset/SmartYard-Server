<?php

namespace tasks;

require_once dirname(__FILE__) . "/IntercomConfigureTask.php";

use DateInterval;
use DateTime;
use Exception;
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

    protected ?Redis $redis = null;
    protected ?PDO_EXT $pdo = null;
    protected ?array $config = null;

    protected ?Logger $logger = null;

    public function __construct(string $title)
    {
        $this->title = $title;
    }

    public abstract function onTask();

    public function onError(Throwable $exception)
    {
    }

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
    private ?DateTime $start = null;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function queue(string $queue): static
    {
        $this->queue = $queue;

        return $this;
    }

    public function high(): static
    {
        $this->queue = TaskManager::HIGH;

        return $this;
    }

    public function medium(): static
    {
        $this->queue = TaskManager::MEDIUM;

        return $this;
    }

    public function low(): static
    {
        $this->queue = TaskManager::LOW;

        return $this;
    }

    public function default(): static
    {
        $this->queue = TaskManager::DEFAULT;

        return $this;
    }

    public function start(DateTime $start): static
    {
        $this->start = $start;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function delay(DateInterval|int $delay): static
    {
        if (!$this->start)
            $this->start = new DateTime();

        if (is_int($delay))
            $this->start->add(new DateInterval('PT' . $delay . 'S'));
        else
            $this->start->add($delay);

        return $this;
    }

    public function dispatch(): bool
    {
        TaskManager::instance()->worker($this->queue ?? 'default')->push($this->start?->getTimestamp() ?? time(), $this->task);

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
        $ids = $this->redis->lRange($this->getWorkerKey(), 0, -1);

        return in_array($id, $ids);
    }

    public function next(): int
    {
        $id = $this->redis->lIndex($this->getWorkerKey(), -1);

        return $id + 1;
    }

    /**
     * Добавляем информацию, что TaskWorker запущен
     * @param int $id
     */
    public function start(int $id)
    {
        $this->redis->lPush($this->getWorkerKey(), $id);

        $this->logger?->info('Start TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    /**
     * Получить команду, адресованную TaskWorker с определенным id
     * @param int $id
     * @return string|null
     */
    public function command(int $id): ?string
    {
        $command = $this->redis->lPop($this->getWorkerIdKey($id));

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
            $ids = $this->redis->lRange($this->getWorkerKey(), 0, -1);

            foreach ($ids as $id) {
                $this->redis->lPush($this->getWorkerIdKey($id), $command);

                $this->logger?->info('Send command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
            }
        } else if ($this->has($id)) {
            $this->redis->lPush($this->getWorkerIdKey($id), $command);

            $this->logger?->info('Send command TaskWorker', ['queue' => $this->queue, 'id' => $id, 'command' => $command]);
        }
    }

    /**
     * Добавляем новую зачаду в TaskWorker
     * Задача добавляется в начало очереди
     * @param int $start
     * @param Task $task
     */
    public function push(int $start, Task $task)
    {
        $this->rawPush($start, serialize($task));

        $this->logger?->info('Push new Task', ['queue' => $this->queue, 'class' => get_class($this)]);
    }

    /**
     * Вытащить и вернуть задачу
     * @return Task|null
     */
    public function pop(): ?Task
    {
        $raw = $this->redis->lPop($this->getWorkerTasksKey());

        if (is_null($raw) || $raw === FALSE)
            return null;

        $this->redis->decr($this->getWorkerSizeKey());

        try {
            $json = json_decode($raw);
            $task = unserialize($json['d']);

            if (!$task)
                return null;

            if (time() < $json['s']) {
                $this->rawPush($json['s'], $json['d']);

                return null;
            }

            return $task;
        } catch (Exception $e) {
            $this->logger?->error('Unserializable error' . PHP_EOL . $e, ['queue' => $this->queue]);

            return null;
        }
    }

    public function size(): int
    {
        return $this->redis->get($this->getWorkerSizeKey());
    }

    /**
     * Удалить все задачи из TaskWorker
     */
    public function clear()
    {
        $this->redis->del($this->getWorkerTasksKey());
        $this->redis->set($this->getWorkerSizeKey(), 0);

        $this->logger?->info('Clear TaskWorker', ['queue' => $this->queue]);
    }

    /**
     * Убираем информацию, что TaskWorker запущен
     * @param int $id
     */
    public function stop(int $id)
    {
        $this->redis->lRem($this->getWorkerKey(), $id, 1);

        $this->logger?->info('Stop TaskWorker', ['queue' => $this->queue, 'id' => $id]);
    }

    private function getWorkerKey(): string
    {
        return 'task:' . $this->queue . ':worker';
    }

    private function getWorkerIdKey(int $id): string
    {
        return 'task:' . $this->queue . ':worker:' . $id;
    }

    private function getWorkerTasksKey(): string
    {
        return 'task:' . $this->queue . ':tasks';
    }

    private function getWorkerSizeKey(): string
    {
        return 'task:' . $this->queue . ':size';
    }

    private function rawPush(int $start, string $task)
    {
        $this->redis->lPush($this->getWorkerTasksKey(), json_encode(['s' => $start, 'd' => $task]));
        $this->redis->incr($this->getWorkerSizeKey());
    }
}

class TaskManager
{
    public const HIGH = 'high';
    public const MEDIUM = 'medium';
    public const LOW = 'low';
    public const DEFAULT = 'default';

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

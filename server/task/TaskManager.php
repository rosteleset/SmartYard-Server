<?php

namespace Selpol\Task;

use Selpol\Logger\Logger;
use Redis;

class TaskManager
{
    public const QUEUE_HIGH = 'high';
    public const QUEUE_MEDIUM = 'medium';
    public const QUEUE_LOW = 'low';
    public const QUEUE_DEFAULT = 'default';

    private static ?TaskManager $instance = null;
    private static ?Logger $logger = null;

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
            $this->workers[$queue] = new TaskWorker($queue, $this->redis, self::$logger);

            $this->redis->sAdd($this->getManagerQueuesKey(), $queue);
        }

        return $this->workers[$queue];
    }

    public function clear()
    {
        $this->redis->del($this->redis->keys('task:*'));

        self::$logger?->info('Clear TaskManager');
    }

    private function getManagerQueuesKey(): string
    {
        return 'task:queues';
    }

    public static function setLogger(?Logger $logger)
    {
        self::$logger = $logger;
    }

    public static function instance(): TaskManager
    {
        global $redis;

        if (is_null(self::$instance))
            self::$instance = new TaskManager($redis);

        return self::$instance;
    }
}
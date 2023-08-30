<?php

namespace Selpol\Task;

use PDO_EXT;
use Redis;
use Throwable;

class TaskProcessor
{
    private TaskWorker $worker;

    private int $id;

    private ?Redis $redis;
    private ?PDO_EXT $db;

    private ?int $sleep;

    public function __construct(TaskWorker $worker, ?int $id)
    {
        $this->worker = $worker;

        if (!is_null($id)) $this->id = $id;
        else $this->id = $this->worker->next();
    }

    public function setRedis(?Redis $redis)
    {
        $this->redis = $redis;
    }

    public function setDb(?PDO_EXT $db)
    {
        $this->db = $db;
    }

    public function setSleep(?int $sleep)
    {
        $this->sleep = $sleep;
    }

    public function register()
    {
        $this->worker->start($this->id);
    }

    public function process()
    {
        $command = $this->worker->popCommand($this->id);

        if ($command !== null) {
            switch ($command) {
                case 'exit':
                    $this->worker->getLogger()->info('TaskWorker exit on command', ['id' => $this->id]);

                    exit(0);
                case 'reset':
                    $this->worker->getLogger()->info('TaskWorker reset on command', ['id' => $this->id]);

                    if (function_exists('opcache_reset'))
                        opcache_reset();

                    break;
            }
        } else {
            $task = $this->worker->popTask();

            if (!is_null($task)) {
                try {
                    $this->worker->setTitle($this->id, $task->title);
                    $this->worker->setProgress($this->id, 0);

                    $task->setRedis($this->redis);
                    $task->setPdo($this->db);
                    $task->setProgressCallable(static fn(int $progress) => $this->worker->setProgress($this->id, $progress));

                    $task->onTask();

                    $this->worker->getLogger()?->info('TaskWorker completed task', ['id' => $this->id]);
                } catch (Throwable $e) {
                    $task->onError($e);

                    $this->worker->getLogger()?->error('TaskWorker error task' . PHP_EOL . $e, ['id' => $this->id]);
                } finally {
                    $this->worker->setTitle($this->id, null);
                    $this->worker->setProgress($this->id, null);
                }
            } else if ($this->sleep) usleep($this->sleep);
        }
    }

    public function unregister()
    {
        $this->worker->stop($this->id);
    }
}
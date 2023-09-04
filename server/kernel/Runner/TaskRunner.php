<?php

namespace Selpol\Kernel\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Service\TaskService;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;
use Throwable;

class TaskRunner implements KernelRunner
{
    private array $argv;

    private LoggerInterface $logger;

    public function __construct(array $argv, ?LoggerInterface $logger = null)
    {
        $this->argv = $argv;

        $this->logger = $logger ?? logger('task');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    function __invoke(Kernel $kernel): int
    {
        $arguments = $this->getArguments();

        $queue = array_key_exists('--queue', $arguments) ? $arguments['--queue'] : 'default';

        $this->registerSignal();
        $this->registerDequeue($kernel, $queue);

        return 0;
    }

    public function onFailed(Throwable $throwable, bool $fatal): int
    {
        $this->logger->error($throwable, ['fatal' => $fatal]);

        return 0;
    }

    private function getArguments(): array
    {
        $args = [];

        for ($i = 1; $i < count($this->argv); $i++) {
            $a = explode('=', $this->argv[$i]);

            $args[$a[0]] = @$a[1];
        }

        return $args;
    }

    private function registerSignal(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
            sapi_windows_set_ctrl_handler(static function (int $event) {
                if ($event == PHP_WINDOWS_EVENT_CTRL_C)
                    exit(0);
            });
        else {
            pcntl_async_signals(true);

            pcntl_signal(SIGINT, static fn() => exit(0));
            pcntl_signal(SIGTERM, static fn() => exit(0));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    private function registerDequeue(Kernel $kernel, string $queue): void
    {
        $service = $kernel->getContainer()->get(TaskService::class);
        $service->setLogger(logger('task'));

        $service->dequeue($queue, new class($queue, logger('task-' . $queue)) implements TaskCallback {
            private string $queue;

            private LoggerInterface $logger;

            public function __construct(string $queue, LoggerInterface $logger)
            {
                $this->queue = $queue;

                $this->logger = $logger;
            }

            public function __invoke(Task $task): void
            {
                $this->logger->info('Dequeue start task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);

                try {
                    $task->setLogger($this->logger);

                    $task->onTask();

                    $this->logger->info('Dequeue complete task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);
                } catch (Throwable $throwable) {
                    $this->logger->info('Dequeue error task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title, 'message' => $throwable->getMessage()]);

                    $task->onError($throwable);
                }
            }
        });
    }
}
<?php

use Psr\Log\LoggerInterface;
use Selpol\Container\Container;
use Selpol\Service\DatabaseService;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;
use Selpol\Service\TaskService;

require_once dirname(__FILE__) . '/vendor/autoload.php';

mb_internal_encoding("UTF-8");

require_once "backends/backend.php";

require_once "controller/api/api.php";

$container = bootstrap();

// TODO: Со временем удалить
$config = $container->get('config');
$db = $container->get(DatabaseService::class);
$redis = $container->get(Redis::class);

$args = [];

for ($i = 1; $i < count($argv); $i++) {
    $a = explode('=', $argv[$i]);

    $args[$a[0]] = @$a[1];
}

$queue = array_key_exists('--queue', $args) ? $args['--queue'] : 'default';

$logger = logger('task');

$manager = TaskService::instance();
$manager->setLogger($logger);

register_shutdown_function(static fn() => $manager->close());

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

try {
    $manager->dequeue($queue, new class($queue, $logger, $container) implements TaskCallback {
        private string $queue;

        private LoggerInterface $logger;
        private Container $container;

        public function __construct(string $queue, LoggerInterface $logger, Container $container)
        {
            $this->queue = $queue;

            $this->logger = $logger;
            $this->container = $container;
        }

        public function __invoke(Task $task)
        {
            $this->logger->info('Dequeue start task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);

            $task->setLogger($this->logger);
            $task->setContainer($this->container);

            try {
                $task->onTask();

                $this->logger->info('Dequeue complete task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);
            } catch (Throwable $throwable) {
                $this->logger->info('Dequeue error task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title, 'message' => $throwable->getMessage()]);

                $task->onError($throwable);
            }
        }
    });
} catch (Exception $exception) {
    $logger->critical($exception);
}
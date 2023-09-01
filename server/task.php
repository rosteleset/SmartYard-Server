<?php

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;
use Selpol\Service\TaskService;

require_once dirname(__FILE__) . '/vendor/autoload.php';

require_once "backends/backend.php";

require_once "controller/api/api.php";

$container = bootstrap();

register_shutdown_function(static fn() => $container->dispose());

// TODO: Со временем удалить
$config = config();

$args = [];

for ($i = 1; $i < count($argv); $i++) {
    $a = explode('=', $argv[$i]);

    $args[$a[0]] = @$a[1];
}

$queue = array_key_exists('--queue', $args) ? $args['--queue'] : 'default';

$logger = new class(logger('task')) implements LoggerInterface {
    use LoggerTrait;

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $this->logger->log($level, $message, $context);
    }
};

$manager = container(TaskService::class);
$manager->setLogger($logger);

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
    $manager->dequeue($queue, new class($queue, $logger) implements TaskCallback {
        private string $queue;

        private LoggerInterface $logger;

        public function __construct(string $queue, LoggerInterface $logger)
        {
            $this->queue = $queue;

            $this->logger = $logger;
        }

        public function __invoke(Task $task)
        {
            $this->logger->info('Dequeue start task', ['queue' => $this->queue, 'class' => get_class($task), 'title' => $task->title]);

            $task->setLogger($this->logger);

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
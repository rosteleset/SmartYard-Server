<?php

use Psr\Log\LoggerInterface;
use Selpol\Task\Task;
use Selpol\Task\TaskCallback;
use Selpol\Task\TaskManager;

require_once dirname(__FILE__) . '/vendor/autoload.php';

mb_internal_encoding("UTF-8");

require_once "backends/backend.php";
require_once "utils/loader.php";
require_once "utils/db_ext.php";

require_once "controller/api/api.php";

try {
    $config = config();
} catch (Exception $e) {
    $config = false;
}

if (!$config) {
    echo 'Config not found';

    exit(1);
}

$redis_cache_ttl = $config["redis"]["cache_ttl"] ?: 3600;

try {
    $redis = new Redis();
    $redis->connect($config["redis"]["host"], $config["redis"]["port"]);

    if (@$config["redis"]["password"])
        $redis->auth(@$config["redis"]["password"]);

    $redis->setex("iAmOk", 1, "1");
} catch (Exception $e) {
    echo 'Redis not connected';

    exit(1);
}

try {
    $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
} catch (Exception $e) {
    echo 'DB not connected';

    exit(1);
}

$args = [];

for ($i = 1; $i < count($argv); $i++) {
    $a = explode('=', $argv[$i]);

    $args[$a[0]] = @$a[1];
}

$queue = array_key_exists('--queue', $args) ? $args['--queue'] : 'default';

$logger = logger('task');

$manager = TaskManager::instance();
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
    $manager->dequeue($queue, new class($redis, $db, $logger) implements TaskCallback {
        private Redis $redis;
        private PDO_EXT $db;
        private LoggerInterface $logger;

        public function __construct(Redis $redis, PDO_EXT $db, LoggerInterface $logger)
        {
            $this->redis = $redis;
            $this->db = $db;
            $this->logger = $logger;
        }

        public function __invoke(Task $task)
        {
            $this->logger->info('Dequeue start task', ['class' => get_class($task), 'title' => $task->title]);

            $task->setLogger($this->logger);
            $task->setRedis($this->redis);
            $task->setPdo($this->db);

            try {
                $task->onTask();

                $this->logger->info('Dequeue complete task', ['class' => get_class($task), 'title' => $task->title]);
            } catch (Throwable $throwable) {
                $this->logger->info('Dequeue error task', ['class' => get_class($task), 'title' => $task->title, 'message' => $throwable->getMessage()]);

                $task->onError($throwable);
            }
        }
    });
} catch (Exception $exception) {
    $logger->critical($exception);
}
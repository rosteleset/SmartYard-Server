<?php

use tasks\TaskManager;

mb_internal_encoding("UTF-8");

require_once "utils/logger.php";
require_once "utils/loader.php";
require_once "utils/guidv4.php";
require_once "utils/db_ext.php";
require_once "utils/checkint.php";
require_once "utils/checkstr.php";
require_once "utils/purifier.php";
require_once "utils/error.php";
require_once "utils/apache_request_headers.php";
require_once "utils/i18n.php";
require_once "utils/validator.php";
require_once "utils/response.php";
require_once "utils/hooks.php";
require_once "utils/email.php";
require_once "utils/is_executable.php";
require_once "utils/parse_uri.php";
require_once "utils/debug.php";

require_once "backends/backend.php";
require_once "tasks/task.php";

require_once "api/api.php";

try {
    $config = loadConfig();
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
$sleep = array_key_exists('--sleep', $args) ? $args['--sleep'] : 10;
$id = array_key_exists('--id', $args) ? $args['--id'] : 1;
$auto = array_key_exists('--auto', $args);

$worker = TaskManager::instance()->worker($queue);
$worker->setLogger(Logger::channel('task'));

if ($worker->has($id)) {
    if ($auto)
        $id = $worker->next();
    else {
        echo 'Id already exist';

        exit(1);
    }
}

$worker->start($id);

register_shutdown_function(static fn() => $worker->stop($id));

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    sapi_windows_set_ctrl_handler(static function (int $event) use ($worker, $id) {
        if ($event == PHP_WINDOWS_EVENT_CTRL_C)
            exit(0);
    });
else {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, static fn() => exit(0));
}

while (true) {
    $command = $worker->command($id);

    if ($command) {
        switch ($command) {
            case 'exit':
                $worker->getLogger()?->info('TaskWorker exit on command', ['queue' => $queue, 'id' => $id]);

                exit(0);
            case 'reset':
                $worker->getLogger()?->info('TaskWorker reset on command', ['queue' => $queue, 'id' => $id]);

                if (function_exists('opcache_reset'))
                    opcache_reset();
        }
    } else {
        $task = $worker->pop();

        if (!is_null($task)) {
            try {
                $task->setRedis($worker->getRedis());
                $task->setPdo($db);
                $task->setLogger($worker->getLogger());
                $task->setConfig($config);

                $task->onTask();

                $worker->getLogger()?->info('TaskWorker completed task', ['queue' => $queue, 'id' => $id]);
            } catch (Throwable $e) {
                $task->onError($e);

                $worker->getLogger()?->error('TaskWorker error task' . PHP_EOL . $e, ['queue' => $queue, 'id' => $id]);
            }
        } else usleep($sleep);
    }
}

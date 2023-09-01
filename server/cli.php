<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\IntercomConfigureTask;
use Selpol\Task\Tasks\QrTask;
use Selpol\Task\Tasks\ReindexTask;

chdir(path(''));

require_once "backends/backend.php";

require_once "controller/api/api.php";

function usage()
{
    global $argv;

    echo "usage: {$argv[0]}
        initialization:
            [--init-db]
            [--admin-password=<password>]
            [--reindex]
            [--clear-cache]
            [--cleanup]

        tests:
            [--check-backends]

        cron:
            [--cron=<minutely|5min|hourly|daily|monthly>]
            [--install-crontabs]
            [--uninstall-crontabs]

        intercom:
            [--intercom-configure-task=<id> [--first]]

        qr:
            [--qr=<houseId> --output=<output> [--flat=<flatId>] [--override]]
        \n";

    exit(1);
}

$args = [];

for ($i = 1; $i < count($argv); $i++) {
    $a = explode('=', $argv[$i]);

    $args[$a[0]] = @$a[1];
}

$params = '';

foreach ($args as $key => $value) {
    if ($value) {
        $params .= " {$key}={$value}";
    } else {
        $params .= " {$key}";
    }
}

$container = bootstrap();
register_shutdown_function(static fn() => $container->dispose());

// TODO: Со временем удалить
$config = config();
$db = $container->get(DatabaseService::class);
$redis = $container->get(Redis::class);

$logger = logger('cli');

$backends = [];

if (count($args) == 1 && array_key_exists("--init-db", $args) && !isset($args["--init-db"])) {
    require_once "sql/install.php";

    init_db();
    $n = clear_cache(true);
    echo "$n cache entries cleared\n\n";

    task(new ReindexTask())->sync();

    exit(0);
}

if (count($args) == 1 && array_key_exists("--cleanup", $args) && !isset($args["--cleanup"])) {
    foreach ($config["backends"] as $backend => $_) {
        $b = backend($backend);

        if ($b) {
            $n = $b->cleanup();

            if ($n !== false) {
                echo "$backend: $n items cleaned\n";
            }
        } else {
            echo "$backend: not found\n";
        }
    }

    exit(0);
}

if (count($args) == 1 && array_key_exists("--reindex", $args) && !isset($args["--reindex"])) {
    $n = clear_cache(true);
    echo "$n cache entries cleared\n";

    task(new ReindexTask())->sync();

    exit(0);
}

if (count($args) == 1 && array_key_exists("--clear-cache", $args) && !isset($args["--clear-cache"])) {
    $n = clear_cache(true);

    $logger->debug('Clear cache', ['entries_count' => $n]);

    echo "$n cache entries cleared\n";

    exit(0);
}

if (count($args) == 1 && array_key_exists("--admin-password", $args) && isset($args["--admin-password"])) {
    try {
        $db->exec("insert into core_users (uid, login, password) values (0, 'admin', 'admin')");
    } catch (Exception $e) {
        //
    }

    try {
        $sth = $db->prepare("update core_users set password = :password, login = 'admin', enabled = 1 where uid = 0");
        $sth->execute([":password" => password_hash($args["--admin-password"], PASSWORD_DEFAULT)]);

        $logger->debug('Update admin password');

        echo "admin account updated\n";
    } catch (Exception $e) {
        echo "admin account update failed\n";
    }
    exit(0);
}

if (count($args) == 1 && array_key_exists("--cron", $args)) {
    $parts = ["minutely", "5min", "hourly", "daily", "monthly"];
    $part = false;

    foreach ($parts as $p)
        if (in_array($p, $args)) {
            $part = $p;

            break;
        }

    if ($part) {
        $start = microtime(true) * 1000;
        $cronBackends = $config['backends'];

        $logger->debug('Processing cron', ['part' => $part, 'backends' => array_keys($cronBackends)]);

        foreach ($cronBackends as $backend_name => $cfg) {
            $backend = backend($backend_name);

            if ($backend) {
                try {
                    if ($backend->cron($part))
                        $logger->debug('Success', ['backend' => $backend_name, 'part' => $part]);
                    else
                        $logger->error('Fail', ['backend' => $backend_name, 'part' => $part]);
                } catch (Exception $e) {
                    $logger->error('Error cron' . PHP_EOL . $e, ['backend' => $backend_name, 'part' => $part]);
                }
            } else $logger->error('Backend not found', ['backend' => $backend_name, 'part' => $part]);
        }

        $logger->debug('Cron done', ['ellapsed_ms' => microtime(true) * 1000 - $start]);
    } else {
        usage();
    }

    exit(0);
}

if (count($args) == 1 && array_key_exists("--install-crontabs", $args) && !isset($args["--install-crontabs"])) {
    $crontab = [];

    exec("crontab -l", $crontab);

    $clean = [];
    $skip = false;

    $cli = PHP_BINARY . " " . __FILE__ . " --cron";

    $lines = 0;

    foreach ($crontab as $line) {
        if ($line === "## RBT crons start, dont touch!!!")
            $skip = true;

        if (!$skip)
            $clean[] = $line;

        if ($line === "## RBT crons end, dont touch!!!")
            $skip = false;

    }

    $clean = explode("\n", trim(implode("\n", $clean)));

    $clean[] = "";

    $clean[] = "## RBT crons start, dont touch!!!";
    $lines++;
    $clean[] = "*/1 * * * * $cli=minutely";
    $lines++;
    $clean[] = "*/5 * * * * $cli=5min";
    $lines++;
    $clean[] = "1 */1 * * * $cli=hourly";
    $lines++;
    $clean[] = "1 1 */1 * * $cli=daily";
    $lines++;
    $clean[] = "1 1 1 */1 * $cli=monthly";
    $lines++;
    $clean[] = "## RBT crons end, dont touch!!!";
    $lines++;

    file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

    system("crontab " . sys_get_temp_dir() . "/rbt_crontab");
    echo "$lines crontabs lines added\n";

    $logger->debug('Install crontabs', ['lines' => $lines]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--uninstall-crontabs", $args) && !isset($args["--uninstall-crontabs"])) {
    $crontab = [];

    exec("crontab -l", $crontab);

    $clean = [];
    $skip = false;

    $lines = 0;

    foreach ($crontab as $line) {
        if ($line === "## RBT crons start, dont touch!!!")
            $skip = true;

        if (!$skip) $clean[] = $line;
        else $lines++;

        if ($line === "## RBT crons end, dont touch!!!")
            $skip = false;
    }

    $clean = explode("\n", trim(implode("\n", $clean)));

    file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

    system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

    echo "$lines crontabs lines removed\n";

    $logger->debug('Uninstall crontabs', ['lines' => $lines]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--check-backends", $args) && !isset($args["--check-backends"])) {
    $all_ok = true;

    foreach ($config["backends"] as $backend => $null) {
        $t = backend($backend);

        if (!$t) {
            echo "loading $backend failed\n";

            $all_ok = false;
        } else {
            try {
                if (!$t->check()) {
                    echo "error checking backend $backend\n";

                    $all_ok = false;
                }
            } catch (Exception $e) {
                print_r($e);

                $all_ok = false;
            }
        }
    }

    if ($all_ok)
        echo "everything is all right\n";

    exit(0);
}

if (count($args) == 1 && array_key_exists('--clear-config', $args) && !isset($args['----clear-config'])) {
    if (file_exists(path('var/cache/env.php')))
        unlink(path('var/cache/env.php'));

    if (file_exists(path('var/cache/config.php')))
        unlink(path('var/cache/config.php'));

    $logger->debug('Clear config and env cache');

    env();
    config();

    exit(0);
}

if (array_key_exists('--intercom-configure-task', $args) && isset($args['--intercom-configure-task'])) {
    $id = $args['--intercom-configure-task'];
    $first = array_key_exists('--first', $args);

    task(new IntercomConfigureTask($id, $first))->sync();

    exit(0);
}

if (array_key_exists('--qr', $args) && isset($args['--qr']) && array_key_exists('--output', $args) && isset($args['--output'])) {
    $houseId = $args['--qr'];
    $output = $args['--output'];

    $flatId = array_key_exists('--flat', $args) && isset($args['--flat']) ? $args['--flat'] : null;
    $override = array_key_exists('--override', $args);

    $uuid = task(new QrTask($houseId, $flatId, $override))->sync();

    if ($uuid)
        fwrite(fopen($output, 'w'), backend('files')->getFileBytes($uuid));

    exit(0);
}

usage();
<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\IntercomConfigureTask;
use Selpol\Task\Tasks\QrTask;
use Selpol\Task\Tasks\ReindexTask;

chdir(__DIR__);

require_once "backends/backend.php";

require_once "controller/api/api.php";

function usage()
{
    global $argv;

    echo "usage: {$argv[0]}
        common parts:
            [--parent-pid=<pid>]
            [--debug]

        demo server:
            [--run-demo-server [--port=<port>]]

        initialization:
            [--init-db]
            [--admin-password=<password>]
            [--reindex]
            [--clear-cache]
            [--cleanup]
            [--init-mobile-issues-project]

        tests:
            [--get-db-version]
            [--check-backends]

        cron:
            [--cron=<minutely|5min|hourly|daily|monthly>]
            [--install-crontabs]
            [--uninstall-crontabs]

        config:
            [--clear-config]
            [--print-config]
            [--write-yaml-config]
            [--write-json-config]

        intercom:
            [--intercom-configure-task=<id> [--first]]

        qr:
            [--qr=<houseId> --output=<output> [--flat=<flatId>] [--override]]

        \n";

    exit(1);
}

$script_result = null;
$script_process_id = -1;
$script_filename = __FILE__;
$script_parent_pid = null;

$args = [];

for ($i = 1; $i < count($argv); $i++) {
    $a = explode('=', $argv[$i]);
    if ($a[0] == '--parent-pid') {
        if (!check_int($a[1])) {
            usage();
        } else {
            $script_parent_pid = $a[1];
        }
    } else
        if ($a[0] == '--debug' && !isset($a[1])) {
//            debugOn();
        } else {
            $args[$a[0]] = @$a[1];
        }
}

$params = '';
foreach ($args as $key => $value) {
    if ($value) {
        $params .= " {$key}={$value}";
    } else {
        $params .= " {$key}";
    }
}

function startup()
{
    global $db, $params, $script_process_id, $script_parent_pid;

    if (@$db) {
        try {
            $script_process_id = $db->insert('insert into core_running_processes (pid, ppid, start, process, params, expire) values (:pid, :ppid, :start, :process, :params, :expire)', [
                "pid" => getmypid(),
                "ppid" => $script_parent_pid,
                "start" => time(),
                "process" => "cli.php",
                "params" => $params,
                "expire" => time() + 24 * 60 * 60,
            ], ["silent"]);
        } catch (Exception $e) {
            //
        }
    }
}

function shutdown()
{
    global $script_process_id, $db, $script_result;

    if (@$db) {
        try {
            $db->modify("update core_running_processes set done = :done, result = :result where running_process_id = :running_process_id", [
                "done" => time(),
                "result" => $script_result,
                "running_process_id" => $script_process_id,
            ], ["silent"]);
        } catch (Exception $e) {
            //
        }
    }
}

function check_if_pid_exists()
{
    global $db;

    if (@$db) {
        try {
            $pids = $db->get("select running_process_id, pid from core_running_processes where done is null", false, [
                "running_process_id" => "id",
                "pid" => "pid",
            ], ["silent"]);

            if ($pids) {
                foreach ($pids as $process) {
                    if (!file_exists("/proc/{$process['pid']}")) {
                        $db->modify("update core_running_processes set done = :done, result = :result where running_process_id = :running_process_id", [
                            "done" => time(),
                            "result" => "unknown",
                            "running_process_id" => $process['id'],
                        ], ["silent"]);
                    }
                }
            }
        } catch (Exception $e) {
            //
        }
    }
}

register_shutdown_function('shutdown');

function is_executable_pathenv($filename): bool
{
    if (is_executable($filename)) {
        return true;
    }
    if ($filename !== basename($filename)) {
        return false;
    }
    $paths = explode(PATH_SEPARATOR, getenv("PATH"));
    foreach ($paths as $path) {
        if (is_executable($path . DIRECTORY_SEPARATOR . $filename)) {
            return true;
        }
    }
    return false;
}

if ((count($args) == 1 || count($args) == 2) && array_key_exists("--run-demo-server", $args) && !isset($args["--run-demo-server"])) {
    $db = null;
    if (is_executable_pathenv(PHP_BINARY)) {
        $port = 8000;

        if (count($args) == 2) {
            if (array_key_exists("--port", $args) && !empty($args["--port"])) {
                $port = $args["--port"];
            } else {
                usage();
            }
        }

        echo "open in your browser:\n\n";
        echo "http://localhost:$port/client/index.html\n\n";
        chdir(__DIR__ . "/..");
        passthru(PHP_BINARY . " -S 0.0.0.0:$port");
    } else {
        echo "no php interpreter found in path\n";
    }
    exit(0);
}

try {
    mb_internal_encoding("UTF-8");
} catch (Exception $e) {
    die("mbstring extension is not available\n");
}

if (!function_exists("curl_init")) {
    die("curl extension is not installed\n");
}

$required_backends = [
    "authentication",
    "authorization",
    "accounting",
    "users",
];

try {
    if (PHP_VERSION_ID < 50600) {
        echo "minimal supported php version is 5.6\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "can't determine php version\n";
    exit(1);
}

$container = bootstrap();

// TODO: Со временем удалить
$config = $container->get('config');
$db = $container->get(DatabaseService::class);
$redis = $container->get(Redis::class);

$logger = logger('cli');

try {
    $query = $db->query("select var_value from core_vars where var_name = 'dbVersion'", PDO::FETCH_ASSOC);
    if ($query) {
        $version = (int)($query->fetch()["var_value"]);
    } else {
        $version = 0;
    }
} catch (Exception $e) {
    $version = 0;
}

$backends = [];
foreach ($required_backends as $backend) {
    if (backend($backend) === false) {
        die("can't load required backend [$backend]\n");
    }
}

if (count($args) == 1 && array_key_exists("--init-db", $args) && !isset($args["--init-db"])) {
    require_once "sql/install.php";

    init_db();
    startup();
    $n = clear_cache(true);
    echo "$n cache entries cleared\n\n";

    task(new ReindexTask())->sync($redis, $db);

    exit(0);
}

startup();

check_if_pid_exists();
if (@$db) {
    $db->modify("delete from core_running_processes where done is null and coalesce(expire, 0) < " . time(), false, ["silent"]);

    $already = (int)$db->get("select count(*) as already from core_running_processes where done is null and params = :params and pid <> " . getmypid(), [
        'params' => $params,
    ], false, ['fieldlify', 'silent']);

    if ($already) {
        $script_result = "already running";
        exit(0);
    }
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

if (count($args) == 1 && array_key_exists("--init-mobile-issues-project", $args) && !isset($args["--init-mobile-issues-project"])) {
    exit(0);
}

if (count($args) == 1 && array_key_exists("--reindex", $args) && !isset($args["--reindex"])) {
    $n = clear_cache(true);
    echo "$n cache entries cleared\n";

    task(new ReindexTask())->sync($redis, $db);

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

    foreach ($parts as $p) {
        if (in_array($p, $args)) {
            $part = $p;

            break;
        }
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
    global $script_filename;

    $crontab = [];
    exec("crontab -l", $crontab);

    $clean = [];
    $skip = false;

    $cli = PHP_BINARY . " " . $script_filename . " --cron";

    $lines = 0;

    foreach ($crontab as $line) {
        if ($line === "## RBT crons start, dont touch!!!") {
            $skip = true;
        }
        if (!$skip) {
            $clean[] = $line;
        }
        if ($line === "## RBT crons end, dont touch!!!") {
            $skip = false;
        }
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
        if ($line === "## RBT crons start, dont touch!!!") {
            $skip = true;
        }
        if (!$skip) {
            $clean[] = $line;
        } else {
            $lines++;
        }
        if ($line === "## RBT crons end, dont touch!!!") {
            $skip = false;
        }
    }

    $clean = explode("\n", trim(implode("\n", $clean)));

    file_put_contents(sys_get_temp_dir() . "/rbt_crontab", trim(implode("\n", $clean)));

    system("crontab " . sys_get_temp_dir() . "/rbt_crontab");

    echo "$lines crontabs lines removed\n";

    $logger->debug('Uninstall crontabs', ['lines' => $lines]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--get-db-version", $args) && !isset($args["--get-db-version"])) {
    echo "dbVersion: $version\n";

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

    if ($all_ok) {
        echo "everything is all right\n";
    }

    exit(0);
}

if (count($args) == 1 && array_key_exists("--write-yaml-config", $args) && !isset($args["--write-yaml-config"])) {
    file_put_contents("config/config.yml", yaml_emit($config));

    exit(0);
}

if (count($args) == 1 && array_key_exists("--write-json-config", $args) && !isset($args["--write-json-config"])) {
    file_put_contents("config/config.json", json_encode($config, JSON_PRETTY_PRINT));

    exit(0);
}

if (count($args) == 1 && array_key_exists("--print-config", $args) && !isset($args["--print-config"])) {
    print_r($config);

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
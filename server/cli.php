<?php

// command line client

use cli\Cli;
use logger\Logger;

chdir(__DIR__);

require_once "logger/Logger.php";

require_once "utils/error.php";
require_once "utils/response.php";
require_once "utils/hooks.php";
require_once "utils/guidv4.php";
require_once "utils/loader.php";
require_once "utils/checkint.php";
require_once "utils/email.php";
require_once "utils/is_executable.php";
require_once "utils/db_ext.php";
require_once "utils/parse_uri.php";
require_once "utils/debug.php";
require_once "utils/i18n.php";

require_once "backends/backend.php";

require_once "api/api.php";

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
            [--check-mail=<your email address>]
            [--get-db-version]
            [--check-backends]

        autoconfigure:
            [--autoconfigure-domophone=<domophone_id> [--first-time]]

        cron:
            [--cron=<minutely|5min|hourly|daily|monthly>]
            [--install-crontabs]
            [--uninstall-crontabs]

        dvr:
            [--run-record-download=<id>]

        config:
            [--clear-config]
            [--print-config]
            [--write-yaml-config]
            [--write-json-config]

        plog:
            [--plog-days --flat=<flat>]
            [--plog --flat=<flat> --day=<day>]
            
        inbox:
            [--inbox --subscriber=<subscriber>]
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
        if (!checkInt($a[1])) {
            usage();
        } else {
            $script_parent_pid = $a[1];
        }
    } else
        if ($a[0] == '--debug' && !isset($a[1])) {
            debugOn();
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

$logger = Logger::channel('cli');

try {
    $config = loadConfig();
} catch (Exception $e) {
    $config = false;
}

if (!$config) {
    echo "config is empty\n";
    exit(1);
}

if (@!$config["backends"]) {
    echo "no backends defined\n";
    exit(1);
}

try {
    $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
} catch (Exception $e) {
    echo "can't open database " . $config["db"]["dsn"] . "\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

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

try {
    $redis = new Redis();
    $redis->connect($config["redis"]["host"], $config["redis"]["port"]);
    if (@$config["redis"]["password"]) {
        $redis->auth($config["redis"]["password"]);
    }
    $redis->setex("iAmOk", 1, "1");
} catch (Exception $e) {
    echo "can't connect to redis server\n";
    exit(1);
}

$backends = [];
foreach ($required_backends as $backend) {
    if (loadBackend($backend) === false) {
        die("can't load required backend [$backend]\n");
    }
}

if (count($args) == 1 && array_key_exists("--init-db", $args) && !isset($args["--init-db"])) {
    require_once "sql/install.php";
    require_once "utils/clear_cache.php";
    require_once "utils/reindex.php";

    init_db();
    startup();
    $n = clearCache(true);
    echo "$n cache entries cleared\n\n";
    reindex();
    echo "\n";
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
    require_once "utils/cleanup.php";
    cleanup();
    exit(0);
}

if (count($args) == 1 && array_key_exists("--init-mobile-issues-project", $args) && !isset($args["--init-mobile-issues-project"])) {
    require_once "utils/mobile_project.php";
    init_mp();
    exit(0);
}

if (count($args) == 1 && array_key_exists("--reindex", $args) && !isset($args["--reindex"])) {
    require_once "utils/reindex.php";
    require_once "utils/clear_cache.php";

    reindex();
    $n = clearCache(true);
    echo "$n cache entries cleared\n";

    $logger->debug('Reindex routes', ['entries_count' => $n]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--clear-cache", $args) && !isset($args["--clear-cache"])) {
    require_once "utils/clear_cache.php";

    $n = clearCache(true);

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

if (count($args) == 1 && array_key_exists("--check-mail", $args) && isset($args["--check-mail"])) {
    $r = email($config, $args["--check-mail"], "test email", "test email");
    if ($r === true) {
        echo "email sended\n";
    } else
        if ($r === false) {
            echo "no email config found\n";
        } else {
            print_r($r);
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
            $backend = loadBackend($backend_name);

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

if ((count($args) == 1 || count($args) == 2) && array_key_exists("--autoconfigure-domophone", $args) && isset($args["--autoconfigure-domophone"])) {
    $domophone_id = $args["--autoconfigure-domophone"];

    $first_time = false;

    if (count($args) == 2) {
        if (array_key_exists("--first-time", $args)) {
            $first_time = true;
        } else {
            usage();
        }
    }

    if (checkInt($domophone_id)) {
        require_once "utils/autoconfigure_domophone.php";

        autoconfigure_domophone($domophone_id, $first_time);

        $logger->debug('Autoconfigure domophone', ['id' => $domophone_id, 'first_time' => $first_time]);

        exit(0);
    } else {
        usage();
    }
}

if (count($args) == 1 && array_key_exists("--install-crontabs", $args) && !isset($args["--install-crontabs"])) {
    require_once "utils/install_crontabs.php";

    $n = installCrontabs();
    echo "$n crontabs lines added\n";

    $logger->debug('Install crontabs', ['lines' => $n]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--uninstall-crontabs", $args) && !isset($args["--uninstall-crontabs"])) {
    require_once "utils/install_crontabs.php";

    $n = unInstallCrontabs();
    echo "$n crontabs lines removed\n";

    $logger->debug('Uninstall crontabs', ['lines' => $n]);

    exit(0);
}

if (count($args) == 1 && array_key_exists("--get-db-version", $args) && !isset($args["--get-db-version"])) {
    echo "dbVersion: $version\n";

    exit(0);
}

if (count($args) == 1 && array_key_exists("--check-backends", $args) && !isset($args["--check-backends"])) {
    $all_ok = true;

    foreach ($config["backends"] as $backend => $null) {
        $t = loadBackend($backend);
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

if (count($args) == 1 && array_key_exists("--run-record-download", $args) && isset($args["--run-record-download"])) {
    $recordId = (int)$args["--run-record-download"];
    $dvr_exports = @loadBackend("dvr_exports");

    if ($dvr_exports && ($uuid = $dvr_exports->runDownloadRecordTask($recordId))) {
        $inbox = loadBackend("inbox");
        $files = loadBackend("files");

        $metadata = $files->getFileMetadata($uuid);

        $msgId = $inbox->sendMessage($metadata['subscriberId'], i18n("dvr.videoReady"), i18n("dvr.threeDays", $config['api']['mobile'], $uuid));
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
    if (file_exists('cache/config.php'))
        unlink('cache/config.php');

    $logger->debug('Clear config cache');

    exit(0);
}

if (count($args) == 2 && array_key_exists("--plog-days", $args) && !isset($args["--plog-days"])) {
    if (array_key_exists("--flat", $args) && !empty($args["--flat"])) {
        $flat = $args["--flat"];
    } else {
        usage();
    }

    $plog = loadBackend("plog");
    $result = $plog->getEventsDays($flat, false);

    var_dump($result);

    exit(0);
}

if (count($args) == 3 && array_key_exists("--plog", $args) && !isset($args["--plog"])) {
    if (array_key_exists("--flat", $args) && !empty($args["--flat"])) {
        $flat = $args["--flat"];
    } else {
        usage();
    }

    if (array_key_exists("--day", $args) && !empty($args["--day"])) {
        $day = $args["--day"];
    } else {
        usage();
    }

    $plog = loadBackend("plog");
    $result = $plog->getDetailEventsByDay($flat, date('Ymd', strtotime($day)));

    var_dump($result);

    exit(0);
}

if (count($args) == 2 && array_key_exists('--inbox', $args) && !isset($args['--inbox'])) {
    if (array_key_exists("--subscriber", $args) && !empty($args["--subscriber"])) {
        $subscriber = $args["--subscriber"];
    } else {
        usage();
    }

    $inbox = loadBackend('inbox');

    $messages = $inbox->getMessages($subscriber, "all", []);

    var_dump($messages);

    exit(0);
}

usage();

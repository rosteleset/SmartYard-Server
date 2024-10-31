<?php

// command line client

    chdir(__DIR__);

    $cli = true;
    $cliError = false;

    require_once "data/backup_db.php";
    require_once "data/install_clickhouse.php";
    require_once "data/install.php";
    require_once "data/install_tt_mobile_template.php";
    require_once "data/schema.php";
    require_once "hw/autoconfigure_device.php";
    require_once "hw/autoconfigure_domophone.php";
    require_once "utils/checkint.php";
    require_once "utils/checkstr.php";
    require_once "utils/cleanup.php";
    require_once "utils/clear_cache.php";
    require_once "utils/clickhouse.php";
    require_once "utils/db_ext.php";
    require_once "utils/debug.php";
    require_once "utils/email.php";
    require_once "utils/error.php";
    require_once "utils/format_usage.php";
    require_once "utils/guidv4.php";
    require_once "utils/i18n.php";
    require_once "utils/install_crontabs.php";
    require_once "utils/is_executable.php";
    require_once "utils/loader.php";
    require_once "utils/mobile_project.php";
    require_once "utils/parse_url_ext.php";
    require_once "utils/purifier.php";
    require_once "utils/reindex.php";
    require_once "utils/response.php";
    require_once "utils/generate_password.php";
    require_once "utils/apache_request_headers.php";
    require_once "utils/mb_levenshtein.php";

    if (file_exists("mzfc/json5/vendor/autoload.php")) {
        require_once "mzfc/json5/vendor/autoload.php";
    }

    require_once "backends/backend.php";

    require_once "api/api.php";

    function usage() {
        global $argv;

        echo formatUsage("usage: {$argv[0]}

            backend:
                [<backend name> [params]]

            common parts:
                [--parent-pid=<pid>]
                [--debug]

            demo server:
                [--run-demo-server [--port=<port>]]

            initialization and update:
                [--init-db [--skip=<versions>|--force=<version>|--set-version=<version>]]
                [--init-clickhouse-db]
                [--admin-password=<password>]
                [--reindex]
                [--clear-cache]
                [--cleanup]
                [--init-mobile-issues-project]
                [--update]
                [--exit-maintenance-mode]
                [--init-tt-mobile-template]
                [--init-monitoring-config]

            tests:
                [--check-mail=<your email address>]
                [--get-db-version]

            backends:
                [--backends-with-cli]
                [--check-backends]

            autoconfigure:
                [--autoconfigure-device=<device_type> --id=<device_id> [--first-time]]

            cron:
                [--cron=<minutely|5min|hourly|daily|monthly>]
                [--install-crontabs]
                [--uninstall-crontabs]

            db:
                [--backup-db]
                [--list-db-backups]
                [--restore-db=<backup_file_without_path_and_extension>]
                [--shema=<schema>]
                [--mongodb-set-fcv=<FeatureCompatibilityVersion>]

            config:
                [--print-config]
                [--strip-config]
        ");

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
        }
        else {
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
            putenv("SPX_ENABLED=1");
            putenv("SPX_REPORT=full");
            putenv("SPX_AUTO_START=1");
            passthru(PHP_BINARY . " -S 0.0.0.0:$port");
        } else {
            echo "no php interpreter found in path\n\n";
        }
        exit(0);
    }

    function shutdown() {
        global $script_process_id, $db, $script_result;

        if (@$db) {
            try {
                $db->modify("update core_running_processes set done = :done, result = :result where running_process_id = :running_process_id", [
                    "done" => time(),
                    "result" => $script_result,
                    "running_process_id" => $script_process_id,
                ], [ "silent" ]);
            } catch (\Exception $e) {
                //
            }
        }
    }

    function startup($skip_maintenance_check = false) {
        global $db, $params, $script_process_id, $script_parent_pid, $script_result;

        register_shutdown_function('shutdown');

        if (@$db) {
            try {
                $script_process_id = $db->insert('insert into core_running_processes (pid, ppid, start, process, params, expire) values (:pid, :ppid, :start, :process, :params, :expire)', [
                    "pid" => getmypid(),
                    "ppid" => $script_parent_pid,
                    "start" => time(),
                    "process" => "cli.php",
                    "params" => $params,
                    "expire" => time() + 24 * 60 * 60,
                ], [ "silent" ]);
            } catch (\Exception $e) {
                //
            }

            $already = (int)$db->get("select count(*) as already from core_running_processes where done is null and params = :params and pid <> " . getmypid(), [
                    'params' => $params,
            ], false, [ 'fieldlify', 'silent' ]);

            if ($already) {
                $script_result = "already running";
                exit(0);
            }

            if (!$skip_maintenance_check) {
                $maintenance = (int)$db->get("select count(*) as maintenance from core_vars where var_name = 'maintenance'", [], false, [ 'fieldlify', 'silent' ]);

                if ($maintenance) {
                    echo "****************************************\n";
                    echo "*       !!! MAINTENANCE MODE !!!       *\n";
                    echo "****************************************\n\n";
                    $script_result = "maintenance mode";
                    exit(0);
                }
            }
        }
    }

    function check_if_pid_exists() {
        global $db;

        if (@$db) {
            try {
                $pids = $db->get("select running_process_id, pid from core_running_processes where done is null", false, [
                    "running_process_id" => "id",
                    "pid" => "pid",
                ], [ "silent" ]);

                if ($pids) {
                    foreach ($pids as $process) {
                        if (!file_exists( "/proc/{$process['pid']}")) {
                            $db->modify("update core_running_processes set done = :done, result = :result where running_process_id = :running_process_id", [
                                "done" => time(),
                                "result" => "unknown",
                                "running_process_id" => $process['id'],
                            ], [ "silent" ]);
                        }
                    }
                }

                $db->modify("delete from core_running_processes where done is not null and coalesce(expire, 0) < " . time(), false, [ "silent" ]);
            } catch (\Exception $e) {
                //
            }
        }
    }

    function maintenance($on) {
        global $db;

        if (@$db) {
            if ($on) {
                $db->insert("insert into core_vars (var_name, var_value) values ('maintenance', '1')");
            } else {
                $db->modify("delete from core_vars where var_name = 'maintenance'");
            }
        } else {
            echo "database is not awailable\n\n";
            exit(1);
        }
    }

    function wait_all() {
        global $db;

        echo "waiting other processes to finish";

        if (@$db) {
            while (true) {
                $exists = (int)$db->get("select count(*) as exists from core_running_processes where done is null and pid <> " . getmypid(), [], false, [ 'fieldlify', 'silent' ]);

                if (!$exists) {
                    break;
                } else {
                    echo " .";
                }

                sleep(5);
            }
        }
        echo " - done\n\n";
    }

    try {
        mb_internal_encoding("UTF-8");
    } catch (Exception $e) {
        die("mbstring extension is not available\n\n");
    }

    if (!function_exists("curl_init")) {
        die("curl extension is not installed\n\n");
    }

    $required_backends = [
        "authentication",
        "authorization",
        "accounting",
        "users",
    ];

    try {
        if (PHP_VERSION_ID < 50600) {
            echo "minimal supported php version is 5.6\n\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "can't determine php version\n\n";
        exit(1);
    }

    if (function_exists("json5_decode")) {
        try {
            $config = @json5_decode(file_get_contents("config/config.json"), true);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $config = false;
        }
    } else {
        try {
            $config = @json_decode(file_get_contents("config/config.json"), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            $config = false;
        }
    }

    if (!$config) {
        echo "config is empty\n\n";
        exit(1);
    }

    if (@!$config["backends"]) {
        echo "no backends defined\n\n";
        exit(1);
    }

    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $e) {
        echo "can't open database " . $config["db"]["dsn"] . "\n\n";
        echo $e->getMessage() . "\n\n";
        exit(1);
    }

    if (@$config["db"]["schema"]) {
        $db->exec("SET search_path TO " . $config["db"]["schema"]);
    }

    try {
        $query = $db->query("select var_value from core_vars where var_name = 'dbVersion'", PDO::FETCH_ASSOC);
        if ($query) {
            $version = (int)($query->fetch()["var_value"]);
        } else {
            $version = 0;
        }
    } catch (\Exception $e) {
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
        echo "can't connect to redis server\n\n";
        exit(1);
    }

    $backends = [];
    foreach ($required_backends as $backend) {
        if (loadBackend($backend) === false) {
            die("can't load required backend [$backend]\n\n");
        }
    }

    if (count($args) && (strpos($argv[1], "--") === false || strpos($argv[1], "--") > 0)) {
        $backend = loadBackend($argv[1]);

        if (!$backend) {
            die("backend \"{$argv[1]}\" not found\n\n");
        }

        unset($args[$argv[1]]);

        if (!$backend->capabilities() || !array_key_exists("cli", $backend->capabilities())) {
            die("command line is not available for backend \"{$argv[1]}\"\n\n");
        }

        $backend->cli($args);

        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--backends-with-cli", $args) && !isset($args["--backends-with-cli"])) {
        $cli = [];

        foreach ($config["backends"] as $b => $p) {
            $e = loadBackend($b);
            if ($e && $e->capabilities() && array_key_exists("cli", $e->capabilities())) {
                $cli[] = $b;
            }
        }

        if (count($cli)) {
            echo "backends with cli support: " . implode(" ", $cli) . "\n\n";
        } else {
            echo "no backends with cli support found\n\n";
        }

        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--exit-maintenance-mode", $args) && !isset($args["--exit-maintenance-mode"])) {
        maintenance(false);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--init-tt-mobile-template", $args) && !isset($args["--init-tt-mobile-template"])) {
        try {
            installTTMobileTemplate();
        } catch (Exception $e) {
            echo $e->getMessage() . "\n\n";
            exit(1);
        }
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--init-monitoring-config", $args) && !isset($args["--init-monitoring-config"])) {
    try {
        $monitoring = loadBackend('monitoring');
        $monitoring->configureMonitoring();
    } catch (Exception $e) {
        echo $e->getMessage() . "\n\n";
        exit(1);
    }
    exit(0);
    }

    startup();

    check_if_pid_exists();

    if (
        (count($args) == 1 && array_key_exists("--init-db", $args) && !isset($args["--init-db"]))
        ||
        (count($args) == 2 && array_key_exists("--init-db", $args) && !isset($args["--init-db"]) && array_key_exists("--skip", $args) && isset($args["--skip"]))
        ||
        (count($args) == 2 && array_key_exists("--init-db", $args) && !isset($args["--init-db"]) && array_key_exists("--force", $args) && isset($args["--force"]))
        ||
        (count($args) == 2 && array_key_exists("--init-db", $args) && !isset($args["--init-db"]) && array_key_exists("--set-version", $args) && isset($args["--set-version"]) && (int)$args["--set-version"])
    ) {
        maintenance(true);
        wait_all();

        backup_db(false);
        echo "\n";

        if (@$args["--set-version"]) {
            $sth = $db->prepare("update core_vars set var_value = :version where var_name = 'dbVersion'");
            $sth->bindParam('version', $args["--set-version"]);
            $sth->execute();
        } else {
            initDB(@$args["--skip"], @$args["--force"]);
        }

        startup(true);
        echo "\n";

        $n = clearCache(true);
        echo "$n cache entries cleared\n\n";

        reindex();
        echo "\n";

        maintenance(false);

        try {
            $db->exec("commit");
        } catch (\Exception $e) {
            //
        }

        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--init-clickhouse-db", $args) && !isset($args["--init-clickhouse-db"])) {
        $clickhouse_config = @$config['clickhouse'];

        $clickhouse = new clickhouse(
            @$clickhouse_config['host'] ?? '127.0.0.1',
            @$clickhouse_config['port'] ?? 8123,
            @$clickhouse_config['username'] ?? 'default',
            @$clickhouse_config['password'] ?? 'qqq',
        );

        initClickhouseDB($clickhouse);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--cleanup", $args) && !isset($args["--cleanup"])) {
        cleanup();
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--init-mobile-issues-project", $args) && !isset($args["--init-mobile-issues-project"])) {
        init_mp();
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--reindex", $args) && !isset($args["--reindex"])) {
        $n = clearCache(true);
        echo "$n cache entries cleared\n\n";
        reindex();
        echo "\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--clear-cache", $args) && !isset($args["--clear-cache"])) {
        $n = clearCache(true);
        echo "$n cache entries cleared\n\n";
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
            $sth->execute([ ":password" => password_hash($args["--admin-password"], PASSWORD_DEFAULT) ]);
            echo "admin account updated\n\n";
        } catch (Exception $e) {
            echo "admin account update failed\n\n";
        }
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--check-mail", $args) && isset($args["--check-mail"])) {
        $r = email($config, $args["--check-mail"], "test email", "test email");
        if ($r === true) {
            echo "email sended\n\n";
        } else
        if ($r === false) {
            echo "no email config found\n\n";
        } else {
            print_r($r);
        }
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--cron", $args)) {
        $parts = [ "minutely", "5min", "hourly", "daily", "monthly" ];
        $part = false;

        foreach ($parts as $p) {
            if (in_array($p, $args)) {
                $part = $p;
            }
        }

        if ($part) {
            foreach ($config["backends"] as $backend_name => $cfg) {
                $backend = loadBackend($backend_name);
                if ($backend) {
                    try {
                        if (!$backend->cron($part)) {
                            echo "$backend_name [$part] fail\n\n";
                        }
                    } catch (\Exception $e) {
                        echo "$backend_name [$part] exception\n\n";
                    }
                }
            }
        } else {
            usage();
        }

        exit(0);
    }

    // TODO: deprecated, dont forget to delete
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
            $idPart = "--id=$domophone_id";
            $firstTimePart = $first_time ? " --first-time'" : "'";
            echo "!!! Deprecated. Use '--autoconfigure-device=domophone $idPart$firstTimePart next time !!!\n\n";

            autoconfigure_domophone($domophone_id, $first_time);
            exit(0);
        } else {
            usage();
        }
    }

    if ((count($args) === 2 || count($args) === 3) && isset($args["--autoconfigure-device"], $args["--id"])) {
        $device_type = $args["--autoconfigure-device"];
        $device_id = $args["--id"];

        $first_time = false;

        if (count($args) === 3) {
            if (array_key_exists("--first-time", $args)) {
                $first_time = true;
            } else {
                usage();
            }
        }

        if (checkInt($device_id)) {
            try {
                autoconfigure_device($device_type, $device_id, $first_time);
                exit(0);
            } catch (Exception $e) {
                $script_result = 'fail';
                echo "!!! FAILED: " . $e->getMessage() . " !!!\n\n";
                exit(1);
            }
        }
    }

    if (count($args) == 1 && array_key_exists("--install-crontabs", $args) && !isset($args["--install-crontabs"])) {
        $n = installCrontabs();
        echo "$n crontabs lines added\n\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--uninstall-crontabs", $args) && !isset($args["--uninstall-crontabs"])) {
        $n = unInstallCrontabs();
        echo "$n crontabs lines removed\n\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--get-db-version", $args) && !isset($args["--get-db-version"])) {
        echo "dbVersion: $version\n\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--check-backends", $args) && !isset($args["--check-backends"])) {
        $all_ok = true;

        foreach ($config["backends"] as $backend => $null) {
            $t = loadBackend($backend);
            if (!$t) {
                echo "loading $backend failed\n\n";
                $all_ok = false;
            } else {
                try {
                    if (!$t->check()) {
                        echo "error checking backend $backend\n\n";
                        $all_ok = false;
                    }
                } catch (\Exception $e) {
                    print_r($e);
                    $all_ok = false;
                }
            }
        }

        if ($all_ok) {
            echo "everything is all right\n\n";
        }
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--strip-config", $args) && !isset($args["--strip-config"])) {
        file_put_contents("config/config.json", json_encode($config, JSON_PRETTY_PRINT));
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--print-config", $args) && !isset($args["--print-config"])) {
        print_r($config);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--backup-db", $args) && !isset($args["--backup-db"])) {
        maintenance(true);
        wait_all();

        backup_db();

        maintenance(false);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--list-db-backups", $args) && !isset($args["--list-db-backups"])) {
        list_db_backups();
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--restore-db", $args) && isset($args["--restore-db"])) {
        maintenance(true);
        wait_all();

        restore_db($args["--restore-db"]);

        maintenance(false);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--schema", $args) && isset($args["--schema"])) {
        maintenance(true);
        wait_all();

        schema($args["--schema"]);

        maintenance(false);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--mongodb-set-fcv", $args) && isset($args["--mongodb-set-fcv"])) {
        maintenance(true);
        wait_all();

        if (@$config["mongo"]["uri"]) {
            $manager = new \MongoDB\Driver\Manager($config["mongo"]["uri"]);
        } else {
            $manager = new \MongoDB\Driver\Manager();
        }

        $command = new \MongoDB\Driver\Command([ "setFeatureCompatibilityVersion" => $args["--mongodb-set-fcv"], "confirm" => true ]);

        try {
            $cursor = $manager->executeCommand('admin', $command);
        } catch(\Exception $e) {
            echo $e->getMessage(), "\n";
            exit;
        }

        $response = $cursor->toArray()[0];

        echo "ok\n";

        maintenance(false);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--update", $args) && !isset($args["--update"])) {
        maintenance(true);
        wait_all();

        backup_db();
        echo "\n";

        chdir(__DIR__);

        $code = false;

        system("git pull", $code);
        echo "\n";

        if ($code !== 0) {
            exit($code);
        }

        initDB();
        echo "\n";

        $clickhouse_config = @$config['clickhouse'];

        $clickhouse = new clickhouse(
            @$clickhouse_config['host'] ?? '127.0.0.1',
            @$clickhouse_config['port'] ?? 8123,
            @$clickhouse_config['username'] ?? 'default',
            @$clickhouse_config['password'] ?? 'qqq',
        );

        initClickhouseDB($clickhouse);
        echo "\n";

        $n = clearCache(true);
        echo "$n cache entries cleared\n\n";

        reindex();
        echo "\n";

        maintenance(false);

        exit(0);
    }

    usage();

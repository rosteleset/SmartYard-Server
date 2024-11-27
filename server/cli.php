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
    require_once "cli/cli.php";

    foreach (scandir(__DIR__ . "/cli") as $file) {
        $c = explode(".php", $file);

        if (count($c) == 2 && $c[0] && !$c[1]) {
            require_once __DIR__ . "/cli/" . $file;

            if (class_exists('\cli\\' . $c[0])) {
                new ('\cli\\' . $c[0])($globalCli);
            }
        }
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

    cli("init", "#", $args);

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
            die("database is not awailable\n\n");
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
            die("minimal supported php version is 5.6\n\n");
        }
    } catch (Exception $e) {
        die("can't determine php version\n\n");
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
        die("config is empty\n\n");
    }

    if (@!$config["backends"]) {
        die("no backends defined\n\n");
    }

    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $e) {
        echo "can't open database " . $config["db"]["dsn"] . "\n\n";
        die($e->getMessage() . "\n\n");
    }

    if (@$config["db"]["schema"]) {
        $db->exec("SET search_path TO " . $config["db"]["schema"]);
    }

    //TODO: rewrite to get method
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
        die("can't connect to redis server\n\n");
        exit(1);
    }

    $backends = [];
    foreach ($required_backends as $backend) {
        if (loadBackend($backend) === false) {
            die("can't load required backend [$backend]\n\n");
        }
    }

    cli("pre", "#", $args);

    startup();

    check_if_pid_exists();

    if (count($args) && (strpos($argv[1], "--") === false || strpos($argv[1], "--") > 0)) {
        $backend = $argv[1];
        unset($args[$argv[1]]);

        cli("run", $backend, $args);

        cliUsage();

        exit(0);
    }

    cli("run", "#", $args);

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

    cliUsage();

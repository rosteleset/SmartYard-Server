<?php

    require_once 'vendor/autoload.php';

    require_once "data/backup_db.php";
    require_once "data/install_clickhouse.php";
    require_once "data/install.php";
    require_once "data/install_tt_mobile_template.php";
    require_once "data/schema.php";
    require_once "hw/autoconfigure_device.php";
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
    require_once "utils/array_is_list.php";

    require_once "backends/backend.php";

    require_once "api/api.php";

    $cli = true;
    $cli_error = false;
    $cli_errors = [];

    $global_cli = [];

    $script_result = null;
    $script_process_id = -1;
    $script_filename = __FILE__;
    $script_parent_pid = null;

    $args = [];

    $required_backends = [
        "authentication",
        "authorization",
        "accounting",
        "users",
    ];

    function cli($stage, $backend = "#", $args = []) {
        global $global_cli, $config;

        if ($config && $config["backends"]) {
            foreach ($config["backends"] as $b => $p) {
                $i = loadBackend($b);

                if ($i) {
                    $c = $i->cliUsage();

                    if ($c && is_array($c) && count($c)) {
                        if (!@$global_cli[$b]) {
                            $global_cli[$b] = [];
                        }
                        $global_cli[$b] = array_merge($global_cli[$b], $c);
                    }
                }
            }
        }

        foreach (@$global_cli[$backend] as $title => $part) {
            foreach ($part as $name => $command) {
                if (array_key_exists("--" . $name, $args)) {
                    if (!@$command["stage"]) {
                        $command["stage"] = "run";
                    }
                    if ($command["stage"] == $stage) {
                        $m = false;
                        if (@$command["params"]) {
                            foreach ($command["params"] as $variants) {
                                //TODO: add params set check
                                $m = true;
                            }
                        } else {
                            $m = true;
                        }
                        if ($m) {
                            //TODO: add param value check
                            if ($backend == "#") {
                                $command["exec"]($args);
                            } else {
                                $i = loadBackend($backend);
                                $i->cli($args);
                            }
                        }
                    }
                }
            }
        }
    }

    function cliUsage() {
        global $global_cli, $argv, $config;

        foreach ($config["backends"] as $b => $p) {
            $i = loadBackend($b);

            if ($i) {
                $c = $i->cliUsage();

                if ($c && is_array($c) && count($c)) {
                    if (!@$global_cli[$b]) {
                        $global_cli[$b] = [];
                    }
                    $global_cli[$b] = array_merge($global_cli[$b], $c);
                }
            }
        }

        foreach ($global_cli as $backend => $cli) {
            if ($backend == "#") {
                echo "usage: {$argv[0]} <params>\n\n";
            } else {
                echo "usage: {$argv[0]} $backend <params>\n\n";
            }

            echo "  common parts:\n\n";
            echo "    --parent-pid=<pid>\n";
            echo "      Set parent pid\n\n";
            echo "    --debug\n";
            echo "      Run with debug\n\n";

            foreach ($cli as $title => $part) {
                echo "  $title:\n\n";

                foreach ($part as $name => $command) {
                    echo "    --$name";
                    if (@$command["value"]) {
                        echo "=<";
                        if (is_array($command["value"])) {
                            echo implode("|", $command["value"]);
                        } else {
                            echo (@$command["placeholder"]) ? $command["placeholder"] : "value";
                        }
                        echo ">";
                    }
                    if (@$command["params"]) {
                        $g = "";
                        foreach ($command["params"] as $variants) {
                            $p = "";
                            foreach ($variants as $prefix => $param) {
                                if (@$param["optional"]) {
                                    $p .= "[";
                                }
                                $p .= "--$prefix";
                                if (@$param["value"]) {
                                    $p .= "=<";
                                    if (is_array($param["value"])) {
                                        $p .= implode("|", $param["value"]);
                                    } else {
                                        $p .= (@$param["placeholder"]) ? $param["placeholder"] : "value";
                                    }
                                    $p .= ">";
                                }
                                if (@$param["optional"]) {
                                    $p .= "]";
                                }
                                $p .= " ";
                            }
                            $g .= trim($p) . " | ";
                        }
                        if ($g) {
                            $g = substr($g, 0, -3);
                        }
                        echo " " . $g;
                    }
                    echo "\n";
                    if (@$command["description"]) {
                        echo "      " . $command["description"] . "\n";
                    }
                    echo "\n";
                }
            }
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
                    "params" => trim($params),
                    "expire" => time() + 24 * 60 * 60,
                ], [ "silent" ]);
            } catch (\Exception $e) {
                //
            }

            $already = (int)$db->get("select count(*) as already from core_running_processes where done is null and params = :params and pid <> " . getmypid(), [
                'params' => trim($params),
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

    chdir(__DIR__);

    foreach (scandir(__DIR__ . "/cli") as $file) {
        $c = explode(".php", $file);

        if (count($c) == 2 && $c[0] && !$c[1]) {
            require_once __DIR__ . "/cli/" . $file;

            if (class_exists('\cli\\' . $c[0])) {
                new ('\cli\\' . $c[0])($global_cli);
            }
        }
    }

    foreach (scandir(__DIR__ . "/cli/custom") as $file) {
        $c = explode(".php", $file);

        if (count($c) == 2 && $c[0] && !$c[1]) {
            require_once __DIR__ . "/cli/custom/" . $file;

            if (class_exists('\cli\\' . $c[0])) {
                new ('\cli\\' . $c[0])($global_cli);
            }
        }
    }

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

    try {
        mb_internal_encoding("UTF-8");
    } catch (\Exception $e) {
        die("mbstring extension is not available\n\n");
    }

    if (!function_exists("curl_init")) {
        die("curl extension is not installed\n\n");
    }

    if (function_exists("json5_decode")) {
        try {
            $config = @json5_decode(file_get_contents("config/config.json"), true);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            $config = false;
        }
    } else {
        try {
            $config = @json_decode(file_get_contents("config/config.json"), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
            $config = false;
        }
    }

    if (!$config) {
        die("config is empty\n\n");
    }

    cli("init", "#", $args);

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
        $db->exec("SET search_path TO " . $config["db"]["schema"] . ", public");
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

    cliUsage();

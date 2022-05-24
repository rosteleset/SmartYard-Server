<?php

    chdir(dirname(__FILE__));

    require_once "utils/response.php";
    require_once "utils/hooks.php";
    require_once "utils/guidv4.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/email.php";
    require_once "utils/is_executable.php";

    require_once "backends/backend.php";

    require_once "api/api.php";

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
            die("minimal supported php version is 5.6\n");
        }
    } catch (Exception $e) {
        die("can't determine php version\n");
    }

    try {
        $config = @json_decode(file_get_contents("config/config.json"), true);
    } catch (Exception $e) {
        die("can't load config file");
    }

    if (!$config) {
        die("config is empty");
    }

    if (@!$config["backends"]) {
        die("no backends defined");
    }

    $backends = [];
    foreach ($required_backends as $backend) {
        if (loadBackend($backend) === false) {
            die("can't load required backend [$backend]");
        }
    }

    try {
        $db = new PDO(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $e) {
        die("can't open database " . $config["db"]["dsn"] . "\n");
    }

    try {
        $redis = new Redis();
        $redis->connect($config["redis"]["host"], $config["redis"]["port"]);
        if (@$config["redis"]["password"]) {
            $redis->auth($config["redis"]["password"]);
        }
        $redis->setex("iAmOk", 1, "1");
    } catch (Exception $e) {
        die("can't connect to redis server\n");
    }

    try {
        $version = (int)$db->query("select var_value from vars where var_name = 'dbVersion'", PDO::FETCH_ASSOC)->fetch()["var_value"];
    } catch (Exception $e) {
        $version = 0;
    }

    $args = [];

    for ($i = 1; $i < count($argv); $i++) {
        $a = explode("=", $argv[$i]);
        $args[$a[0]] = @$a[1];
    }

    if (count($args) == 1 && array_key_exists("--init-db", $args) && !isset($args["--init-db"])) {
        require_once "sql/install.php";
        init_db();
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--reindex", $args) && !isset($args["--reindex"])) {
        require_once "utils/reindex.php";
        reindex();
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--clear-cache", $args) && !isset($args["--clear-cache"])) {
        $keys = $redis->keys("cache_*");
        $n = 0;
        foreach ($keys as $key) {
            $redis->del($key);
            $n++;
        }
        echo "$n cache entries cleared\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--admin-password", $args) && isset($args["--admin-password"])) {
        try {
            $db->exec("insert into users (uid, login, password) values (0, 'admin', 'admin')");
        } catch (Exception $e) {
            //
        }
        $sth = $db->prepare("update users set password = :password, login = 'admin', enabled = 1 where uid = 0");
        $sth->execute([ ":password" => password_hash($args["--admin-password"], PASSWORD_DEFAULT) ]);
        echo "admin account updated\n";
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--check-mail", $args) && isset($args["--check-mail"])) {
        $r = email($config, $args["--check-mail"], "test email", "test email");
        if ($r === true) {
            echo "email sended\n";
            exit(0);
        }
        if ($r === false) {
            echo "no email config found\n";
            exit(0);
        }
        print_r($r);
        exit(0);
    }

    if (count($args) == 1 && array_key_exists("--run-demo-server", $args) && !isset($args["--run-demo-server"])) {
        if (is_executable_pathenv(PHP_BINARY)) {
            echo "open in your browser:\n\n";
            echo "http://localhost:8000/client/index.html\n\n";
            chdir(dirname(__FILE__) . "/..");
            passthru(PHP_BINARY . " -S 127.0.0.1:8000");
        } else {
            echo "no php interpreter found in path\n";
        }
        exit(0);
    }

    echo "usage: {$argv[0]}
        [--init-db]
        [--admin-password=<password>]
        [--reindex]
        [--clear-cache]
        [--check-mail=<your email address>]
        [--run-demo-server]
    \n";

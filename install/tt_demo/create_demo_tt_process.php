<?php

    require_once "../../server/utils/error.php";
    require_once "../../server/utils/guidv4.php";
    require_once "../../server/utils/loader.php";
    require_once "../../server/utils/checkint.php";
    require_once "../../server/utils/email.php";
    require_once "../../server/utils/is_executable.php";
    require_once "../../server/utils/db_ext.php";
    require_once "../../server/utils/parse_uri.php";
    require_once "../../server/utils/debug.php";
    require_once "../../server/utils/i18n.php";

    require_once "../../server/backends/backend.php";

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

    try {
        $config = @json_decode(file_get_contents("../../server/config/config.json"), true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        echo "can't load config file\n";
        echo strtolower($e->getMessage()) . "\n";
        exit(1);
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
        exit(1);
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

    $files = loadBackend("files");

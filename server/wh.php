<?php

    $cli = false;
    $cli_error = false;

    require_once 'vendor/autoload.php';

    mb_internal_encoding("UTF-8");

    require_once "backends/backend.php";

    require_once "utils/checkstr.php";
    require_once "utils/loader.php";
    require_once "utils/db_ext.php";
    require_once "utils/error.php";
    require_once "utils/api_exec.php";
    require_once "utils/api_response.php";
    require_once "utils/purifier.php";
    require_once "utils/i18n.php";
    require_once "utils/apache_request_headers.php";
    require_once "utils/mb_levenshtein.php";
    require_once "utils/array_is_list.php";

    require_once "utils/checkint.php";
    require_once "utils/guidv4.php";

    // load configuration
    try {
        $config = loadConfiguration();
        if ($config instanceof Exception){
            throw new Exception ($config->getMessage());
        }
    } catch (Exception $err) {
        response(555, false, false, $err->getMessage());
        exit(1);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET') {
        $m = explode('/', trim($_SERVER["REQUEST_URI"], '/'));
        if (count($m) >= 2 && $m[0] === 'wh') {
            $method = $m[1];

            if ($m[2]){
                $param = $m[2] ?? null;
            }

            if (file_exists(__DIR__ . "/wh/{$method}.php")) {
                // Redis connection
                try {
                    $redis = new Redis();
                    $redis->connect($config["redis"]["host"], $config["redis"]["port"]);
                    if (@$config["redis"]["password"]) {
                        $redis->auth($config["redis"]["password"]);
                    }
                    $redis->setex("iAmOk", 1, "1");
                } catch (Exception $err) {
                    error_log(print_r($err, true));
                    response(555, false, false, "Can't connect to Redis");
                    exit(1);
                }

                // db connection
                try {
                    $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
                } catch (Exception $err) {
                    error_log(print_r($err, true));
                    response(500, false, false, "Can't open database " . $config["db"]["dsn"]);
                    exit(1);
                }

                if (@$config["db"]["schema"]) {
                    $db->exec("SET search_path TO " . $config["db"]["schema"] . ", public");
                }

                if (count($_GET)) {
                    foreach ($_GET as $key => $value) {
                        if (gettype($value) == "string") {
                            $params[$key] = urldecode($value);
                        } else {
                            $params[$key] = $value;
                        }
                    }
                }

                if (count($_POST)) {
                    foreach ($_POST as $key => $value) {
                        if (gettype($value) == "string") {
                            $params[$key] = urldecode($value);
                        } else {
                            $params[$key] = $value;
                        }
                    }
                }

                $_RAW = json_decode(file_get_contents("php://input"), true);

                if ($_RAW && count($_RAW)) {
                    foreach ($_RAW as $key => $value) {
                        $params[$key] = $value;
                    }
                }

                $backends = [];
                foreach ($required_backends as $backend) {
                    if (loadBackend($backend) === false) {
                        error_log("noRequiredBackend");
                        response(555, [
                            "error" => "noRequiredBackend",
                        ]);
                    }
                }

                require_once __DIR__ . "/wh/{$method}.php";
            }
        }
    }

    response(404);
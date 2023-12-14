<?php
    mb_internal_encoding("UTF-8");

    require_once "backends/backend.php";
    require_once "utils/loader.php";
    require_once "utils/db_ext.php";
    require_once "utils/error.php";
    require_once "utils/api_exec.php";
    require_once "utils/api_response.php";

    // load configuration
    try {
        $config = loadConfiguration();
        if ($config instanceof Exception){
            throw new Exception ($config->getMessage());
        }
    } catch (Exception $err) {
        response(555, null, null, $err->getMessage());
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

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $raw_postdata = file_get_contents("php://input");
        $postdata = json_decode($raw_postdata, true);

        if (!isset($postdata)) {
            response(405, ["error"=>"post body"]);
        }

        $path = explode("?", $_SERVER["REQUEST_URI"])[0];

        $server = parse_url($config["api"]["internal"]);

        if ($server && $server['path']) {
            $path = substr($path, strlen($server['path']));
        }

        if ($path && $path[0] == '/') {
            $path = substr($path, 1);
        }

        $m = explode('/', $path);

        array_unshift($m, "internal");
        array_unshift($m, false);

        if (count($m) == 4 && !$m[0] && $m[1] == 'internal') {
            $module = $m[2];
            $method = $m[3];
            if (file_exists(__DIR__ . "/internal/{$module}/{$method}.php")) {
                require_once __DIR__ . "/internal/{$module}/{$method}.php";
            }
        }

        response(405);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $m = explode('/', $_SERVER["REQUEST_URI"]);
        if (count($m) == 5 && !$m[0] && $m[1] == 'internal') {
            $module = $m[2];
            $method = $m[3];
            $param = $m[4];
            require_once __DIR__ . "/internal/{$module}/{$method}.php";
        }

    }

    response(404);
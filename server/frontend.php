<?php

use Selpol\Task\Tasks\EmailTask;

require_once './vendor/autoload.php';

$real_ip_header = 'HTTP_X_FORWARDED_FOR';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("Content-Type: text/html;charset=ISO-8859-1");
    http_response_code(204);

    return;
}

require_once "utils/loader.php";
require_once "utils/db_ext.php";

require_once "backends/backend.php";

require_once "api/api.php";

$required_backends = ["authentication", "authorization", "accounting", "users"];

$config = false;
$db = false;
$redis = false;

$http_authorization = @$_SERVER['HTTP_AUTHORIZATION'];
$refresh = array_key_exists('X-Api-Refresh', request_headers());

try {
    mb_internal_encoding("UTF-8");
} catch (Exception $e) {
    error_log(print_r($e, true));

    response(555, ["error" => "mbstring"]);
}

try {
    $config = loadConfig();
} catch (Exception $e) {
    $config = false;
}

if (!$config) response(555, ["error" => "noConfig"]);
if (@!$config["backends"]) response(555, ["error" => "noBackends"]);

$ip = false;

if (isset($_SERVER['REMOTE_ADDR']))
    $ip = $_SERVER['REMOTE_ADDR'];
else if (isset($_SERVER[$real_ip_header]))
    $ip = $_SERVER[$real_ip_header];

if (!$ip)
    response(555, ["error" => "noIp"]);

$redis_cache_ttl = $config["redis"]["cache_ttl"] ?: 3600;

try {
    $redis = new Redis();
    $redis->connect($config["redis"]["host"], $config["redis"]["port"]);

    if (@$config["redis"]["password"])
        $redis->auth($config["redis"]["password"]);

    $redis->setex("iAmOk", 1, "1");
} catch (Exception $e) {
    error_log(print_r($e, true));

    response(555, ["error" => "redis"]);
}

try {
    $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
} catch (Exception $e) {
    error_log(print_r($e, true));

    response(555, ["error" => "PDO"]);
}

$path = explode("?", $_SERVER["REQUEST_URI"])[0];

$server = parse_url($config["api"]["frontend"]);

if ($server && $server['path']) $path = substr($path, strlen($server['path']));
if ($path && $path[0] == '/') $path = substr($path, 1);

$m = explode('/', $path);

$api = @$m[0];
$method = @$m[1];

$params = [];

if (count($m) >= 3)
    $params["_id"] = urldecode($m[2]);

$params["_path"] = ["api" => $api, "method" => $method];

$params["_request_method"] = @$_SERVER['REQUEST_METHOD'];
$params["ua"] = @$_SERVER["HTTP_USER_AGENT"];

$clearCache = false;

function response(int $code = 204, mixed $data = false)
{
    global $params, $backends, $db;

    $db = null;

    header('Content-Type: application/json');
    http_response_code($code);

    if ($code == 204) {
        $backends["accounting"]->log($params, $code);

        exit;
    }

    if ($data)
        echo json_encode($data, JSON_UNESCAPED_UNICODE);

    if ($backends && $backends["accounting"]) $backends["accounting"]->log($params, $code);
    else {
        $login = @($params["_login"] ?: $params["login"]);
        $login = $login ?: "-";

        error_log("{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} [$code]: {$_SERVER['REQUEST_METHOD']} $login {$_SERVER["REQUEST_URI"]}");
    }

    exit;
}

function forgot($params)
{
    if (@$params["eMail"]) {
        $uid = $params["_backends"]["users"]->getUidByEMail($params["eMail"]);
        if ($uid !== false) {
            $keys = $params["_redis"]->keys("forgot_*_" . $uid);

            if (!count($keys)) {
                $token = md5(guid_v4());
                $params["_redis"]->setex("forgot_" . $token . "_" . $uid, 900, "1");

                task(new EmailTask($params["eMail"], "password restoration", "<a href='{$params['_config']['server']}/accounts/forgot?token=$token'>{$params['_config']['server']}/accounts/forgot?token=$token</a>"));
            }
        }
    }

    if (@$params["token"]) {
        $keys = $params["_redis"]->keys("forgot_{$params["token"]}_*");

        $uid = false;

        foreach ($keys as $key) {
            $params["_redis"]->del($key);
            $uid = explode("_", $key)[2];
        }

        if ($uid !== false) {
            $pw = generate_password();
            $params["_backends"]["users"]->setPassword($uid, $pw);
            $user = $params["_backends"]["users"]->getUser($uid);

            task(new EmailTask($user["eMail"], "password restoration", "your new password is $pw"));

            $keys = $params["_redis"]->keys("auth_*_$uid");

            foreach ($keys as $key)
                $params["_redis"]->del($key);

            echo "check your mailbox for your new password";

            exit;
        }
    }

    if (@$params["available"])
        if ($params["_backends"]["users"]->capabilities()["mode"] !== "rw" || !$params["_config"]["email"])
            response(403);

    response();
}

if (count($_GET))
    foreach ($_GET as $key => $value)
        if ($key == "_token") $http_authorization = "Bearer " . urldecode($value);
        else if ($key == "_refresh") $refresh = true;
        else if ($key == "_clearCache") $clearCache = true;
        else if ($key !== "_") $params[$key] = urldecode($value);

if (count($_POST))
    foreach ($_POST as $key => $value)
        if ($key == '_token') $http_authorization = "Bearer " . urldecode($value);
        else if ($key == "_refresh") $refresh = true;
        else if ($key == "_clearCache") $clearCache = true;
        else $params[$key] = urldecode($value);

$_RAW = json_decode(file_get_contents("php://input"), true);

if ($_RAW && count($_RAW))
    foreach ($_RAW as $key => $value)
        if ($key == '_token') $http_authorization = "Bearer " . $value;
        else if ($key == "_refresh") $refresh = true;
        else if ($key == "_clearCache") $clearCache = true;
        else $params[$key] = $value;

$backends = [];
foreach ($required_backends as $backend)
    if (loadBackend($backend) === false)
        response(555, ["error" => "noRequiredBackend"]);

$auth = false;

if ($api == "server" && $method == "ping") {
    $params["_login"] = @$params["login"] ?: "-";
    $params["_ip"] = $ip;

    response(200, "pong");
} else
    if ($api == "authentication" && $method == "login") {
        if (!@$params["login"] || !@$params["password"]) {
            $params["_login"] = @$params["login"] ?: "-";
            $params["_ip"] = $ip;

            response(403, ["error" => "noCredentials"]);
        }
    } else {
        if ($http_authorization) {
            $auth = $backends["authentication"]->auth($http_authorization, @$_SERVER["HTTP_USER_AGENT"], $ip);

            if (!$auth) {
                $params["_ip"] = $ip;
                $params["_login"] = '-';

                response(403, ["error" => "tokenNotFound"]);
            }
        } else {
            $params["_ip"] = $ip;
            $params["_login"] = '-';

            response(403, ["error" => "noToken"]);
        }
    }

if ($http_authorization && $auth) {
    $params["_uid"] = $auth["uid"];
    $params["_login"] = $auth["login"];
    $params["_token"] = $auth["token"];

    foreach ($backends as $backend)
        $backend->setCreds($auth["uid"], $auth["login"]);
}

$params["_md5"] = md5(print_r($params, true));

$params["_config"] = $config;
$params["_redis"] = $redis;
$params["_db"] = $db;

$params["_backends"] = $backends;

$params["_ip"] = $ip;

if (@$params["_login"])
    $redis->set("last_" . md5($params["_login"]), time());

if ($api == "accounts" && $method == "forgot") {
    forgot($params);
} else
    if (file_exists(__DIR__ . "/api/$api/$method.php")) {
        if ($backends["authorization"]->allow($params)) {
            $cache = false;

            if ($params["_request_method"] === "GET") {
                try {
                    $cache = json_decode($redis->get("cache_" . $params["_md5"]) . "_" . $auth["uid"], true);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }
            }

            if ($cache && !$refresh) {
                header("X-Api-Data-Source: cache_" . $params["_md5"] . "_" . $auth["uid"]);
                $code = array_key_first($cache);

                response($code, $cache[$code]);

            } else {
                header("X-Api-Data-Source: db");

                if ($clearCache)
                    clear_cache($auth["uid"]);

                require_once __DIR__ . "/api/$api/$method.php";

                if (class_exists("\\api\\$api\\$method")) {
                    try {
                        $result = call_user_func(["\\api\\$api\\$method", $params["_request_method"]], $params);

                        $code = array_key_first($result);

                        if ((int)$code) {
                            if ($params["_request_method"] == "GET" && (int)$code === 200) {
                                $ttl = (array_key_exists("cache", $result)) ? ((int)$cache) : $redis_cache_ttl;
                                $redis->setex("cache_" . $params["_md5"] . "_" . $auth["uid"], $ttl, json_encode($result));
                            }

                            response($code, $result[$code]);
                        } else
                            response(555, ["error" => "resultCode",]);
                    } catch (Exception $e) {
                        error_log(print_r($e, true));

                        response(555, ["error" => "internal",]);
                    }
                } else response(405, ["error" => "methodNotFound",]);
            }
        } else response(403, ["error" => "accessDenied",]);
    } else response(404, ["error" => "methodNotFound"]);
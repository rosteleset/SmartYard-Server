<?php

    $real_ip_header = 'HTTP_X_FORWARDED_FOR';

    // mobile client API support

    $cli = false;
    $cli_error = false;
    $mobile = true;

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

    if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
        header("Content-Type: text/html;charset=ISO-8859-1");
        http_response_code(204);
        return;
    }

    mb_internal_encoding("UTF-8");

    require_once "backends/backend.php";
    require_once "utils/loader.php";
    require_once "utils/guidv4.php";
    require_once "utils/db_ext.php";
    require_once "utils/checkint.php";
    require_once "utils/checkstr.php";
    require_once "utils/purifier.php";
    require_once "utils/error.php";
    require_once "utils/apache_request_headers.php";
    require_once "utils/i18n.php";
    require_once "utils/mb_levenshtein.php";

    $RBTServices = [
        'internet' => [
            "icon" => "internet",
            "title" => i18n("services.internet"),
            "description" => i18n("services.internetDescription"),
            "canChange" => "t",
        ],
        'iptv' => [
            "icon" => "iptv",
            "title" => i18n("services.iptv"),
            "description" => i18n("services.iptvDescription"),
            "canChange" => "t",
        ],
        'ctv' => [
            "icon" => "ctv",
            "title" => i18n("services.ctv"),
            "description" => i18n("services.ctvDescription"),
            "canChange" => "t",
        ],
        'phone' => [
            "icon" => "phone",
            "title" => i18n("services.phone"),
            "description" => i18n("services.phoneDescription"),
            "canChange" => "t",
        ],
        'cctv' => [
            "icon" => "cctv",
            "title" => i18n("services.cctv"),
            "description" => i18n("services.cctvDescription"),
            "canChange" => "f",
        ],
        'domophone' => [
            "icon" => "domophone",
            "title" => i18n("services.domophone"),
            "description" => i18n("services.domophoneDescription"),
            "canChange" => "f",
        ],
        'gsm' => [
            "icon" => "gsm",
            "title" => i18n("services.gsm"),
            "description" => i18n("services.gsmDescription"),
            "canChange" => "t",
        ],
        'faceid' => [
            "icon" => "faceid",
            "title" => i18n("services.faceid"),
            "description" => i18n("services.faceidDescription"),
            "canChange" => "t",
        ],
    ];

    $bearer = false;
    $config = false;
    $subscriber = false;

    $offsetForCityId = 1000000;
    $emptyStreetIdOffset = 1000000;

    try {
        $config = @json_decode(file_get_contents(__DIR__ . "/config/config.json"), true);
    } catch (Exception $e) {
        $config = false;
    }

    if (!$config) {
        error_log("noConfig");

        response(555, [
            "error" => "noConfig",
        ]);
    }

    $backends = [];

    try {
        $redis = new Redis();
        $redis->connect($config["redis"]["host"], $config["redis"]["port"]);
        if (@$config["redis"]["password"]) {
            $redis->auth($config["redis"]["password"]);
        }
        $redis->setex("iAmOk", 1, "1");
    } catch (Exception $e) {
        error_log(print_r($e, true));
        response(555, [
            "error" => "redis",
        ]);
    }

    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        response(555, [
            "error" => "PDO",
        ]);
    }

    if (@$config["db"]["schema"]) {
        $db->exec("SET search_path TO " . $config["db"]["schema"]);
    }

    function response($code = 204, $data = false, $name = false, $message = false) {

        $response_codes = [
            200 => [ 'name' => i18n("responses.200Name"), 'message' => i18n("responses.200Message") ],
            201 => [ 'name' => i18n("responses.201Name"), 'message' => i18n("responses.201Message") ],
            202 => [ 'name' => i18n("responses.202Name"), 'message' => i18n("responses.202Message") ],
            203 => [ 'name' => i18n("responses.203Name"), 'message' => i18n("responses.203Message") ],
            204 => [ 'name' => i18n("responses.204Name"), 'message' => i18n("responses.204Message") ],
            205 => [ 'name' => i18n("responses.205Name"), 'message' => i18n("responses.205Message") ],
            206 => [ 'name' => i18n("responses.206Name"), 'message' => i18n("responses.206Message") ],
            207 => [ 'name' => i18n("responses.207Name"), 'message' => i18n("responses.207Message") ],
            208 => [ 'name' => i18n("responses.208Name"), 'message' => i18n("responses.208Message") ],
            226 => [ 'name' => i18n("responses.226Name"), 'message' => i18n("responses.226Message") ],
            400 => [ 'name' => i18n("responses.400Name"), 'message' => i18n("responses.400Message") ],
            401 => [ 'name' => i18n("responses.401Name"), 'message' => i18n("responses.401Message") ],
            402 => [ 'name' => i18n("responses.402Name"), 'message' => i18n("responses.402Message") ],
            403 => [ 'name' => i18n("responses.403Name"), 'message' => i18n("responses.403Message") ],
            404 => [ 'name' => i18n("responses.404Name"), 'message' => i18n("responses.404Message") ],
            405 => [ 'name' => i18n("responses.405Name"), 'message' => i18n("responses.405Message") ],
            406 => [ 'name' => i18n("responses.406Name"), 'message' => i18n("responses.406Message") ],
            407 => [ 'name' => i18n("responses.407Name"), 'message' => i18n("responses.407Message") ],
            408 => [ 'name' => i18n("responses.408Name"), 'message' => i18n("responses.408Message") ],
            409 => [ 'name' => i18n("responses.409Name"), 'message' => i18n("responses.409Message") ],
            410 => [ 'name' => i18n("responses.410Name"), 'message' => i18n("responses.410Message") ],
            411 => [ 'name' => i18n("responses.411Name"), 'message' => i18n("responses.411Message") ],
            412 => [ 'name' => i18n("responses.412Name"), 'message' => i18n("responses.412Message") ],
            413 => [ 'name' => i18n("responses.413Name"), 'message' => i18n("responses.413Message") ],
            414 => [ 'name' => i18n("responses.414Name"), 'message' => i18n("responses.414Message") ],
            415 => [ 'name' => i18n("responses.415Name"), 'message' => i18n("responses.415Message") ],
            416 => [ 'name' => i18n("responses.416Name"), 'message' => i18n("responses.416Message") ],
            417 => [ 'name' => i18n("responses.417Name"), 'message' => i18n("responses.417Message") ],
            418 => [ 'name' => i18n("responses.418Name"), 'message' => i18n("responses.418Message") ],
            419 => [ 'name' => i18n("responses.419Name"), 'message' => i18n("responses.419Message") ],
            421 => [ 'name' => i18n("responses.421Name"), 'message' => i18n("responses.421Message") ],
            422 => [ 'name' => i18n("responses.422Name"), 'message' => i18n("responses.422Message") ],
            423 => [ 'name' => i18n("responses.423Name"), 'message' => i18n("responses.423Message") ],
            424 => [ 'name' => i18n("responses.424Name"), 'message' => i18n("responses.424Message") ],
            426 => [ 'name' => i18n("responses.426Name"), 'message' => i18n("responses.426Message") ],
            428 => [ 'name' => i18n("responses.428Name"), 'message' => i18n("responses.428Message") ],
            429 => [ 'name' => i18n("responses.429Name"), 'message' => i18n("responses.429Message") ],
            431 => [ 'name' => i18n("responses.431Name"), 'message' => i18n("responses.431Message") ],
            449 => [ 'name' => i18n("responses.449Name"), 'message' => i18n("responses.449Message") ],
            451 => [ 'name' => i18n("responses.451Name"), 'message' => i18n("responses.451Message") ],
            499 => [ 'name' => i18n("responses.499Name"), 'message' => i18n("responses.499Message") ],
            503 => [ 'name' => i18n("responses.503Name"), 'message' => i18n("responses.503Message") ],
        ];

        header('Content-Type: application/json');
        http_response_code($code);

        if ((int)$code == 204) {
            exit;
        }

        $ret = [
            'code' => $code,
        ];

        if (!$message) {
            if ($name) {
                $message = $name;
            } else {
                $message = @$response_codes[$code]['message'];
            }
        }

        if (!$name) {
            $name = @$response_codes[$code]['name'];
        }

        if ($name) {
            $ret['name'] = $name;
        }

        if ($message) {
            $ret['message'] = $message;
        }

        if ($data) {
            $ret['data'] = $data;
        }

        echo json_encode($ret, JSON_UNESCAPED_UNICODE);

        exit;
    }

    function mkdir_r($dirName, $rights = 0777) {
        $dirs = explode('/', $dirName);
        $dir = '';
        foreach ($dirs as $part) {
            $dir .= $part.'/';
            if (!is_dir($dir) && strlen($dir) > 0) {
                mkdir($dir, $rights);
                chmod($dir, $rights);
            }
        }
    }

    function auth() {
        global $_SERVER, $bearer, $subscriber, $device, $real_ip_header;

        $households = loadBackend("households");

        $ip = false;

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (!$ip) {
            if (isset($_SERVER[$real_ip_header])) {
                $ip = $_SERVER[$real_ip_header];
            }
        }

        $ip = long2ip(ip2long($_SERVER['REMOTE_ADDR']));

        if ($ip == '127.0.0.1' && !@$_SERVER['HTTP_AUTHORIZATION'] && $_GET['phone']) {
            $p = trim($_GET['phone']);
            $bearer = false;
            $subscribers = $households->getSubscribers("mobile", $p);
            if ($subscribers) {
                $subscriber = $subscribers[0];
                $bearer = $subscriber["authToken"];
            }
            $devices = $households->getDevices("subscriber", $subscriber["subscriberId"]);
            if ($devices) {
                $device = $devices[0];
                $bearer = $device["authToken"];
            }
            if (!$bearer) {
                response(403, false, "Ошибка авторизации", "Ошибка авторизации");
            }
        } else {
            if (!@$_SERVER['HTTP_AUTHORIZATION']) {
                response(403, false, "Ошибка авторизации", "Ошибка авторизации");
            }
            $bearer = @trim(explode('Bearer', $_SERVER['HTTP_AUTHORIZATION'])[1]);
            if (!$bearer) {
                response(422, false, "Отсутствует токен авторизации", "Отсутствует токен авторизации");
            }
            $t_ = $bearer;
            $bearer = false;
            $devices = $households->getDevices("authToken", $t_);
            if ($devices) {
                $device = $devices[0];
                $bearer = $device["authToken"];
                $subscriber = $households->getSubscribers("id", $device["subscriberId"])[0];
            } else {
                response(401, false, "Не авторизован", "Не авторизован");
            }

            $headers = apache_request_headers();

            $updateDevice = [ "ip" => $ip ];

            if (@$headers['Accept-Language'] && @$headers['X-System-Info']) {
                $ua = $headers['X-System-Info'];
                $ua = str_replace(", ", ",", $ua);
                $updateDevice["ua"] = $headers['Accept-Language'] . ',' . $ua;
            }

            if (@$headers['X-App-Version']) {
                $updateDevice["version"] = $headers['X-App-Version'];
            }

            $households->modifyDevice($device["deviceId"], $updateDevice);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $raw_postdata = file_get_contents("php://input");
        $postdata = json_decode($raw_postdata, true);

        $path = explode("?", $_SERVER["REQUEST_URI"])[0];

        $server = parse_url($config["api"]["mobile"]);

        if ($server && $server['path']) {
            $path = substr($path, strlen($server['path']));
        }

        $path = trim($path, '/');
        $m = explode('/', $path);

        array_unshift($m, "mobile");
        array_unshift($m, false);

        if (count($m) == 4 && !$m[0] && $m[1] == 'mobile') {
            $module = $m[2];
            $method = $m[3];
            if (file_exists(__DIR__ . "/mobile/{$module}/custom/{$method}.php")) {
                require_once __DIR__ . "/mobile/{$module}/custom/{$method}.php";
            } else
            if (file_exists(__DIR__ . "/mobile/{$module}/{$method}.php")) {
                require_once __DIR__ . "/mobile/{$module}/{$method}.php";
            }
        }

        response(405);
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $m = explode('/', $_SERVER["REQUEST_URI"]);
        if (count($m) == 5 && !$m[0] && $m[1] == 'mobile') {
            $module = $m[2];
            $method = $m[3];
            $param = $m[4];
            require_once __DIR__ . "/mobile/{$module}/{$method}.php";
        }
    }

    response(404);

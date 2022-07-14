<?php

    // asterisk support

    require_once "utils/error.php";
    require_once "utils/guidv4.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/db_ext.php";
    require_once "backends/backend.php";

    header('Content-Type: application/json');

    try {
        $config = @json_decode(file_get_contents("config/config.json"), true);
    } catch (Exception $e) {
        echo "can't load config file\n";
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
    } catch (Exception $e) {
        echo "can't connect to redis server\n";
        exit(1);
    }

    function paramsToResponse($params) {
        $r = "";

        foreach ($params as $param => $value) {
            $r .= urlencode($param) . "=" . urlencode($value) . "&";
        }

        return $r;
    }

    function getExtension($extension, $section) {

        // domophone panel
        if ($extension[0] === "1" && strlen($extension) === 5) {
            switch ($section) {
                case "aors":
                    return [
                        "id" => $extension,
                        "max_contacts" => "1",
                        "remove_existing" => "yes"
                    ];

                case "auths":
                    $domophones = loadBackend("domophones");

                    $panel = $domophones->getDomophone((int)substr($extension, 1));

                    if ($panel) {
                        return [
                            "id" => $extension,
                            "username" => $extension,
                            "auth_type" => "userpass",
                            "password" => $panel["credentials"],
                        ];
                    }

                    break;

                case "endpoints":
                    $domophones = loadBackend("domophones");

                    $panel = $domophones->getDomophone((int)substr($extension, 1));

                    if ($panel) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $panel["callerId"],
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
                            "rtp_symmetric" => "no",
                            "force_rport" => "no",
                            "rewrite_contact" => "yes",
                            "timers" => "no",
                            "direct_media" => "no",
                            "allow_subscribe" => "yes",
                            "dtmf_mode" => "rfc4733",
                            "ice_support" => "no",
                        ];
                    }

                    break;
            }
        }

        // mobile extension
        if ($extension[0] === "2" && strlen($extension) === 10) {

        }
    }

/*
        mysql_query("insert into ps_aors (id, max_contacts, remove_existing, synchronized, expire) values ('"..extension.."', 1, 'yes', true, addtime(now(), '00:03:00'))")
        mysql_query("insert ignore into ps_auths (id, auth_type, password, username, synchronized) values ('"..extension.."', 'userpass', '"..hash.."', '"..extension.."', true)")
        mysql_query("insert ignore into ps_endpoints (id, auth, outbound_auth, aors, context, disallow, allow, dtmf_mode, rtp_symmetric, force_rport, rewrite_contact, direct_media, transport, ice_support, synchronized) values ('"..extension.."', '"..extension.."', '"..extension.."', '"..extension.."', 'default', 'all', 'opus,h264', 'rfc4733', 'yes', 'yes', 'yes', 'no', 'transport-tcp', 'yes', true)")
*/

    $path = explode("/", @$_SERVER["PATH_INFO"]);

    switch ($path[1]) {
        case "aors":
        case "auths":
        case "endpoints":
            echo paramsToResponse(getExtension($_POST["id"], $path[1]));
            break;

        case "extensions":
            $_RAW = json_decode(file_get_contents("php://input"), true);

            switch ($path[2]) {
                case "log":
                    error_log($_RAW);
                    break;

                case "autoopen":
                    $households = loadBackend("households");

                    $flat = $households->getFlat((int)$_RAW);

                    $rabbit = (int)$flat["whiteRabbit"];

                    echo json_encode(strtotime($flat["autoOpen"]) > time() || ($rabbit && strtotime($flat["lastOpened"]) + $rabbit * 60 > time()));
                    break;

                case "flat":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlat((int)$_RAW));
                    break;

                case "domophone":
                    $households = loadBackend("households");

                    echo json_encode($households->getDomophone((int)$_RAW));
                    break;

                case "openDoor":
                    $households = loadBackend("households");
                    $domophone = $households->getDomophone($_GET["id"]);

                    require_once "hw/domophones/beward/dks/dks15374.php";

                    $model = new dks15374($domophone["ip"], $domophone["credentials"], $domophone["port"]);

                    header("Content-Type: image/jpeg");

                    echo $model->camshot();

                    break;
            }
            break;
    }


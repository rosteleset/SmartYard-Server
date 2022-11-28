<?php

    // asterisk support

    require_once "utils/error.php";
    require_once "utils/guidv4.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/db_ext.php";
    require_once "utils/debug.php";

    require_once "backends/backend.php";

    header('Content-Type: application/json');

    try {
        $config = @json_decode(file_get_contents(__DIR__ . "/config/config.json"), true);
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

        if ($params) {
            foreach ($params as $param => $value) {
                $r .= urlencode($param) . "=" . urlencode($value) . "&";
            }
        }

        return $r;
    }

    function getExtension($extension, $section) {
        global $redis;

        // domophone panel
        if ($extension[0] === "1" && strlen($extension) === 6) {
            $domophones = loadBackend("households");
            $panel = $domophones->getDomophone((int)substr($extension, 1));

            switch ($section) {
                case "aors":
                    if ($panel && $panel["credentials"]) {
                        return [
                            "id" => $extension,
                            "max_contacts" => "1",
                            "remove_existing" => "yes"
                        ];
                    }
                    break;

                case "auths":

                    if ($panel && $panel["credentials"]) {
                        return [
                            "id" => $extension,
                            "username" => $extension,
                            "auth_type" => "userpass",
                            "password" => $panel["credentials"],
                        ];
                    }
                    break;

                case "endpoints":
                    if ($panel && $panel["credentials"]) {
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
            switch ($section) {
                case "aors":
                    $cred = $redis->get("mobile_extension_" . $extension);

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "max_contacts" => "1",
                            "remove_existing" => "yes"
                        ];
                    }

                    break;

                case "auths":
                    $cred = $redis->get("mobile_extension_" . $extension);

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "username" => $extension,
                            "auth_type" => "userpass",
                            "password" => $cred,
                        ];
                    }

                    break;

                case "endpoints":
                    $cred = $redis->get("mobile_extension_" . $extension);

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $extension,
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
                            "rtp_symmetric" => "yes",
                            "force_rport" => "yes",
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

        // sip extension
        if ($extension[0] === "4" && strlen($extension) === 10) {

        }

        // webrtc extension
        if ($extension[0] === "7" && strlen($extension) === 10) {
            switch ($section) {
                case "aors":
                    $cred = $redis->get("webrtc_" . md5($extension));

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "max_contacts" => "1",
                            "remove_existing" => "yes"
                        ];
                    }

                    break;

                case "auths":
                    $cred = $redis->get("webrtc_" . md5($extension));

                    if ($cred) {
                        return [
                            "id" => $extension,
                            "username" => $extension,
                            "auth_type" => "userpass",
                            "password" => $cred,
                        ];
                    }

                    break;

                case "endpoints":
                    $cred = $redis->get("webrtc_" . md5($extension));

                    $users = loadBackend("users");
                    $user = $users->getUser((int)substr($extension, 1));

                    if ($user && $cred) {
                        return [
                            "id" => $extension,
                            "auth" => $extension,
                            "outbound_auth" => $extension,
                            "aors" => $extension,
                            "callerid" => $user["realName"],
                            "context" => "default",
                            "disallow" => "all",
                            "allow" => "alaw,h264",
//                            "rtp_symmetric" => "no",
//                            "force_rport" => "no",
//                            "rewrite_contact" => "yes",
//                            "timers" => "no",
//                            "direct_media" => "no",
//                            "allow_subscribe" => "yes",
                            "dtmf_mode" => "rfc4733",
//                            "ice_support" => "no",
                            "webrtc" => "yes",
                        ];
                    }

                    break;
            }
        }
    }

/*
        mysql_query("insert into ps_aors (id, max_contacts, remove_existing, synchronized, expire) values ('"..extension.."', 1, 'yes', true, addtime(now(), '00:03:00'))")
        mysql_query("insert ignore into ps_auths (id, auth_type, password, username, synchronized) values ('"..extension.."', 'userpass', '"..hash.."', '"..extension.."', true)")
        mysql_query("insert ignore into ps_endpoints (id, auth, outbound_auth, aors, context, disallow, allow, dtmf_mode, rtp_symmetric, force_rport, rewrite_contact, direct_media, transport, ice_support, synchronized) values ('"..extension.."', '"..extension.."', '"..extension.."', '"..extension.."', 'default', 'all', 'alaw,h264', 'rfc4733', 'yes', 'yes', 'yes', 'no', 'transport-tcp', 'yes', true)")
*/

    $path = $_SERVER["REQUEST_URI"];

    $server = parse_url($config["api"]["asterisk"]);

    if ($server && $server['path']) {
        $path = substr($path, strlen($server['path']));
    }

    if ($path && $path[0] == '/') {
        $path = substr($path, 1);
    }

    $path = explode("/", $path);

    switch ($path[0]) {
        case "aors":
        case "auths":
        case "endpoints":
            if (@$_POST["id"]) echo paramsToResponse(getExtension($_POST["id"], $path[0]));
            break;

        case "extensions":
            $params = json_decode(file_get_contents("php://input"), true);

            switch ($path[1]) {
                case "log":
                    logMsg($params);
                    error_log($params);

                    break;

                case "debug":
                    debugMsg($params);
                    error_log($params);

                    break;

                case "autoopen":
                    $households = loadBackend("households");

                    //TODO
                    // add checking for false, if object doesn't exists

                    $flat = $households->getFlat((int)$params);

                    $rabbit = (int)$flat["whiteRabbit"];

                    echo json_encode(strtotime($flat["autoOpen"]) > time() || ($rabbit && $flat["lastOpened"] + $rabbit * 60 > time()));

                    break;

                case "flat":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlat((int)$params));
                    break;

                case "flatIdByPrefix":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlats("flatIdByPrefix", $params));

                    break;

                case "subscribers":
                    $households = loadBackend("households");

                    echo json_encode($households->getSubscribers("flat", (int)$params));

                    break;

                case "domophone":
                    $households = loadBackend("households");

                    echo json_encode($households->getDomophone((int)$params));

                    break;

                case "entrance":
                    $households = loadBackend("households");

                    $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => (int)$params, "output" => "0" ]);

                    if ($entrances) {
                        echo json_encode($entrances[0]);
                    } else {
                        echo json_encode(false);
                    }

                    break;

                case "camshot":
                    if ($params["domophoneId"] >= 0) {
                        $households = loadBackend("households");

                        $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $params["domophoneId"], "output" => "0" ]);

                        if ($entrances && $entrances[0]) {
                            $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);

                            if ($cameras && $cameras[0]) {
                                $model = loadCamera($cameras[0]["model"], $cameras[0]["url"], $cameras[0]["credentials"]);

                                $redis->setex("shot_" . $params["hash"], 3 * 60, $model->camshot());
                                $redis->setex("live_" . $params["hash"], 3 * 60, json_encode([
                                    "model" => $cameras[0]["model"],
                                    "url" => $cameras[0]["url"],
                                    "credentials" => $cameras[0]["credentials"],
                                ]));

                                echo $params["hash"];
                            }
                        }
                    } else {
                        $redis->setex("shot_" . $params["hash"], 3 * 60, file_get_contents(__DIR__ . "/hw/cameras/fake/img/callcenter.jpg"));
                        $redis->setex("live_" . $params["hash"], 3 * 60, json_encode([
                            "model" => "fake.json",
                            "url" => "callcenter.jpg",
                            "credentials" => "none",
                        ]));

                        echo $params["hash"];
                    }

                    break;

                case "push":
                    $isdn = loadBackend("isdn");

                    $isdn->push([
                        "token" => $params["token"],
                        "type" => $params["tokenType"],
                        "hash" => $params["hash"],
                        "extension" => $params["extension"],
                        "server" => $config["sip_servers"][0]["ip"],
                        "port" => $config["sip_servers"][0]["sip_tcp_port"],
                        "transport" => 'tcp',
                        "dtmf" => $params["dtmf"],
                        "timestamp" => time(),
                        "ttl" => 30,
                        "platform" => (int)$params["platform"]?"ios":"android",
                        "callerId" => $params["callerId"],
                        "flatId" => $params["flatId"],
                        "flatNumber" => $params["flatNumber"],
                        "stun" => $config["sip_servers"][0]["stun_server"],
                        "stunTransport" => "udp",
                        "title" => $config["sip_servers"][0]["incoming_title"],
                    ]);

                    break;
            }
            break;
    }


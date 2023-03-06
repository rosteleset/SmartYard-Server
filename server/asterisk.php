<?php

    // asterisk support

    require_once "utils/error.php";
    require_once "utils/guidv4.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/db_ext.php";
    require_once "utils/debug.php";
    require_once "utils/i18n.php";

    require_once "backends/backend.php";

    header('Content-Type: application/json');

    try {
        $config = @json_decode(file_get_contents(__DIR__ . "/config/config.json"), true);
    } catch (Exception $e) {
        $config = false;
    }

    if (!$config) {
        try {
            $config = @json_decode(json_encode(yaml_parse_file(__DIR__ . "/config/config.yml")), true);
        } catch (Exception $e) {
            $config = false;
        }
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
        echo $e->getMessage() . "\n";
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
                            "callerid" => $extension,
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
                            "ice_support" => "yes",
                        ];
                    }

                    break;
            }
        }

        // sip extension
        if ($extension[0] === "4" && strlen($extension) === 10) {
            $households = loadBackend('households');

            $flatId = (int)substr($extension, 1);
            $cred = $households->getFlat($flatId)['sipPassword'];

            switch ($section) {
                case "aors":
                    if ($cred) {
                        return [
                            "id" => $extension,
                            "max_contacts" => "1",
                            "remove_existing" => "yes"
                        ];
                    }

                    break;

                case "auths":
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
                            "dtmf_mode" => "rfc4733",
                            "webrtc" => "yes",
                        ];
                    }

                    break;
            }
        }
    }

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

                    break;

                case "debug":
                    debugMsg($params);

                    break;

                case "autoopen":
                    $households = loadBackend("households");

                    //TODO
                    // add checking for false, if object doesn't exists

                    $flat = $households->getFlat((int)$params);

                    $rabbit = (int)$flat["whiteRabbit"];

                    echo json_encode($flat["autoOpen"] > time() || ($rabbit && $flat["lastOpened"] + $rabbit * 60 > time()));

                    break;

                case "flat":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlat((int)$params));

                    break;

                case "flatIdByPrefix":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlats("flatIdByPrefix", $params));

                    break;

                case "apartment":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlats("apartment", $params));

                    break;

                case "subscribers":
                    $households = loadBackend("households");

                    echo json_encode($households->getSubscribers("flatId", (int)$params));

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

                case "server":
                    $sip = loadBackend("sip");

                    if ($sip) {

                    }

                    break;

                case "push":
                    $isdn = loadBackend("isdn");
                    $sip = loadBackend("sip");
                    $server = $sip->server("extension", $params["extension"]);

                    $params = [
                        "token" => $params["token"],
                        "type" => $params["tokenType"],
                        "hash" => $params["hash"],
                        "extension" => $params["extension"],
                        "server" => $server["ip"],
                        "port" => @$server["sip_tcp_port"] ? : 5060,
                        "transport" => "tcp",
                        "dtmf" => $params["dtmf"],
                        "timestamp" => time(),
                        "ttl" => 30,
                        "platform" => (int)$params["platform"]?"ios":"android",
                        "callerId" => $params["callerId"],
                        "flatId" => $params["flatId"],
                        "flatNumber" => $params["flatNumber"],
                        "title" => i18n("sip.incomingTitle"),
                    ];

                    $stun = $sip->stun($params["extension"]);
                    if ($stun) {
                        $params["stun"] = $stun;
                        $params["stunTransport"] = "udp";
                    }

                    $isdn->push($params);

                    break;
            }
            break;
    }


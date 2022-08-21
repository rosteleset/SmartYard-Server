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
            switch ($section) {
                case "aors":
                    return [
                        "id" => $extension,
                        "max_contacts" => "1",
                        "remove_existing" => "yes"
                    ];

                case "auths":
                    $domophones = loadBackend("households");

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
                    $domophones = loadBackend("households");

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

    $path = explode("/", @$_SERVER["REQUEST_URI"]);
    array_shift($path);

    switch ($path[1]) {
        case "aors":
        case "auths":
        case "endpoints":
            if (@$_POST["id"]) echo paramsToResponse(getExtension($_POST["id"], $path[1]));
            break;

        case "extensions":
            $params = json_decode(file_get_contents("php://input"), true);

            switch ($path[2]) {
                case "log":
                    error_log($params);
                    break;

                case "autoopen":
                    $households = loadBackend("households");

                    //TODO
                    // add checking for false, if object doesn't exists

                    $flat = $households->getFlat((int)$params);

                    $rabbit = (int)$flat["whiteRabbit"];

                    echo json_encode(strtotime($flat["autoOpen"]) > time() || ($rabbit && strtotime($flat["lastOpened"]) + $rabbit * 60 > time()));
                    break;

                case "flat":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlat((int)$params));
                    break;

                case "flatIdByPrefix":
                    $households = loadBackend("households");

                    echo json_encode($households->getFlats("domophoneAndNumber", $params));
                    break;

                case "subscribers":
                    $households = loadBackend("households");

                    echo json_encode($households->getSubscribers("flat", (int)$params));
                    break;

                case "domophone":
                    $households = loadBackend("households");

                    echo json_encode($households->getDomophone((int)$params));
                    break;

                case "camshot":
                    $households = loadBackend("households");
/*
                    $domophone = $households->getDomophone($params["domophoneId"]);

                    $model = loadDomophone($domophone["model"], $domophone["url"], $domophone["credentials"]);

                    $redis->setex("shot_" . $params["hash"], 3 * 60, $model->camshot());
                    $redis->setex("live_" . $params["hash"], 3 * 60, json_encode([
                        "model" => $domophone["model"],
                        "url" => $domophone["url"],
                        "credentials" => $domophone["credentials"],
                    ]));

                    echo $params["hash"];
*/

                    $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $params["domophoneId"], "output" => "0" ]);

                    if ($entrances && $entrances[0]) {
                        $camera = $households->getCamera($entrances[0]["cameraId"]);
                        $model = loadCamera($camera["model"], $camera["url"], $camera["credentials"]);

                        $redis->setex("shot_" . $params["hash"], 3 * 60, $model->camshot());
                        $redis->setex("live_" . $params["hash"], 3 * 60, json_encode([
                            "model" => $camera["model"],
                            "url" => $camera["url"],
                            "credentials" => $camera["credentials"],
                        ]));

                        echo $params["hash"];
                    }

                    break;

                case "push":
/*
        token = token,
        type = type,
        platform = platform,
        extension = extension,
        hash = hash,
        callerId = callerId,
        flatId = flatId,
        dtmf = dtmf,
        phone = phone,
        uniq = channel.CDR("uniqueid"):get(),
        flatNumber = flatNumber,
 */

/*
                    if (req.query.hash) {
                        let data = {
                            server: 'dm.lanta.me',
                            port: '54675',
                            transport: 'tcp',
                            extension: req.query.extension.toString(),
                            hash: req.query.hash,
                            dtmf: req.query.dtmf?req.query.dtmf:'1',
                            timestamp: Math.round((new Date()).getTime()/1000).toString(),
                            ttl: '30',
                            callerId: req.query.caller_id,
                            platform: req.query.platform,
                            flatId: req.query.flat_id,
                            flatNumber: req.query.flat_number,
                        };
                        if (false) {
                            data.turn = 'turn:37.235.209.140:3478';
                            data.turnTransport = 'udp';
                        }
                        if (true) {
                            data.stun = 'stun:37.235.209.140:3478';
                            data.stun_transport = 'udp';
                            data.stunTransport = 'udp';
                        }
                        if (req.query.platform == 'ios') {
                            realPush({
                                title: "Входящий вызов",
                                body: req.query.caller_id,
                                tag: "voip",
                            }, data, {
                                priority: 'high',
                                mutableContent: true,
                                collapseKey: 'voip',
                            }, req.query.token, req.query.type, res);
                            pushed = true;
                        }
                        if (req.query.platform == 'android') {
                            realPush({}, data, {
                                priority: 'high',
                                mutableContent: false,
                            }, req.query.token, req.query.type, res);
                            pushed = true;
                        }
                    }
 */

                    $isdn = loadBackend("isdn");

                    file_put_contents("/tmp/test_php_push", print_r($params, true));

                    $isdn->push($data);

                    break;
            }
            break;
    }


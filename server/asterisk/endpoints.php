<?php

    error_log("\n\n***** ENDPOINTS:" . print_r($_POST, true) . "\n");

    $uri = $_SERVER["PATH_INFO"];
    while ($uri[0] === "/") {
        $uri = substr($uri, 1);
    }

    function paramsToResponse($params) {
        $r = "";

        foreach ($params as $param => $value) {
            $r .= urlencode($param) . "=" . urlencode($value) . "&";
        }

        return $r;
    }

    $clients = [
        "10001" => [
            "id" => "10001",
            "auth" => "10001",
            "outbound_auth" => "10001",
            "aors" => "10001",
            "callerid" => "10001",
            "context" => "default",
            "disallow" => "all",
            "allow" => "alaw,h264",
//                "allow" => "opus,h264",
//                "allow" => "opus",
//                "webrtc" => "yes",
            "rtp_symmetric" => "no",
            "force_rport" => "no",
            "rewrite_contact" => "yes",
            "timers" => "no",
            "direct_media" => "no",
            "allow_subscribe" => "yes",
            "dtmf_mode" => "rfc4733",
            "ice_support" => "no",
        ],
        "10002" => [
            "id" => "10002",
            "auth" => "10002",
            "outbound_auth" => "10002",
            "aors" => "10002",
            "callerid" => "10002",
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
        ]
    ];

    switch (@$_POST["id_LIKE"]) {
        case "%":
            echo paramsToResponse($clients["10001"]) . "\r\n";
            echo paramsToResponse($clients["10002"]) . "\r\n";
            break;
    }

    switch (@$_POST["id"]) {
        case "10001":
        case "10002":
            echo paramsToResponse($clients[$_POST["id"]]);
            break;
    }


<?php

    function paramsToResponse($params) {
        $r = "";

        foreach ($params as $param => $value) {
            $r .= urlencode($param) . "=" . urlencode($value) . "&";
        }

        return $r;
    }

    $aors = [
        "10001" => [
            "id" => "10001",
            "max_contacts" => "1",
            "remove_existing" => "yes"
        ],
        "10002" => [
            "id" => "10002",
            "max_contacts" => "1",
            "remove_existing" => "yes"
        ],
    ];

    $auths = [
        "10001" => [
            "id" => "10001",
            "username" => "10001",
            "auth_type" => "userpass",
            "password" => "123456",
        ],
        "10002" => [
            "id" => "10002",
            "username" => "10002",
            "auth_type" => "userpass",
            "password" => "123456",
        ],
    ];

    $endpoints = [
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

    $path = explode("/", @$_SERVER["PATH_INFO"]);

    switch ($path[1]) {
        case "aors":
            error_log("\n\n***** AORS:" . print_r($_POST, true) . "\n");
            $clients = $aors;
            break;

        case "auths":
            error_log("\n\n***** AUTHS:" . print_r($_POST, true) . "\n");
            $clients = $auths;
            break;

        case "endpoints":
            error_log("\n\n***** ENDPOINTS:" . print_r($_POST, true) . "\n");
            $clients = $endpoints;
            break;

        case "extensions":
            // extensions.lua helper

            echo json_encode([
                "a" => "b",
            ]);

            $_RAW = json_decode(file_get_contents("php://input"), true);

            error_log(print_r($_RAW, true));
            break;
    }

    switch ($path[1]) {
        case "aors":
        case "auths":
        case "endpoints":
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
        break;
    }


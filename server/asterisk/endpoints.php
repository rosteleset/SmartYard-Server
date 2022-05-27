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

    /*
        mysql("update ps_endpoints set auth='$d', outbound_auth='$d', aors='$d', callerid='$d', context='default', disallow='all', allow='opus,h264', webrtc='yes', rtp_symmetric='yes', force_rport='yes', rewrite_contact='yes', timers='no', direct_media='no', allow_subscribe='yes', dtmf_mode='rfc4733', ice_support='yes', synchronized=true where id='$d'");
     */

    switch (@$_POST["id_LIKE"]) {
        case "%":
            echo paramsToResponse([
                "id" => 10001,
                "auth" => 10001,
                "outbound_auth" => 10001,
                "aors" => 10001,
                "callerid" => 10001,
                "context" => "default",
                "disallow" => "all",
//                "allow" => "alaw,h264",
//                "allow" => "opus,h264",
                "allow" => "opus",
                "webrtc" => "yes",
                "rtp_symmetric" => "no",
                "force_rport" => "no",
                "rewrite_contact" => "yes",
                "timers" => "no",
                "direct_media" => "yes",
                "allow_subscribe" => "yes",
                "dtmf_mode" => "rfc4733",
                "ice_support" => "no",
            ]);
            break;
    }

    switch (@$_POST["id"]) {
        case "10001":
            echo paramsToResponse([
                "id" => 10001,
                "auth" => 10001,
                "outbound_auth" => 10001,
                "aors" => 10001,
                "callerid" => 10001,
                "context" => "default",
                "disallow" => "all",
//                "allow" => "alaw,h264",
//                "allow" => "opus,h264",
                "allow" => "opus",
                "webrtc" => "yes",
                "rtp_symmetric" => "no",
                "force_rport" => "no",
                "rewrite_contact" => "yes",
                "timers" => "no",
                "direct_media" => "yes",
                "allow_subscribe" => "yes",
                "dtmf_mode" => "rfc4733",
                "ice_support" => "no",
            ]);
            break;
    }


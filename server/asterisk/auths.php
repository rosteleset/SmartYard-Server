<?php

    error_log("\n\n***** AUTHS:" . print_r($_POST, true) . "\n");

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


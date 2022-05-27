<?php

    error_log("\n\n***** AUTHS:" . print_r($_POST, true) . "\n");

    function paramsToResponse($params) {
        $r = "";

        foreach ($params as $param => $value) {
            $r .= urlencode($param) . "=" . urlencode($value) . "&";
        }

        return $r;
    }

    switch (@$_POST["id_LIKE"]) {
        case "%":
            echo paramsToResponse([
                "id" => 10001,
                "username" => 10001,
                "auth_type" => "userpass",
                "password" => "123456",
            ]);
            break;
    }

    switch (@$_POST["id"]) {
        case "10001":
            echo paramsToResponse([
                "id" => 10001,
                "username" => 10001,
                "auth_type" => "userpass",
                "password" => "123456",
            ]);
            break;
    }


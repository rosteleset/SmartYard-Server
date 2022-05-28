<?php

    error_log("\n\n***** AORS:" . print_r($_POST, true) . "\n");

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
            "max_contacts" => "1",
            "remove_existing" => "yes"
        ],
        "10002" => [
            "id" => "10002",
            "max_contacts" => "1",
            "remove_existing" => "yes"
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


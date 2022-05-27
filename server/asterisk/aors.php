<?php

    error_log("\n\n***** AORS:" . print_r($_POST, true) . "\n");

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
                "max_contacts" => 1,
                "remove_existing" => "yes"
            ]);
            break;
    }

    switch (@$_POST["id"]) {
        case "10001":
            echo paramsToResponse([
                "id" => 10001,
                "max_contacts" => 1,
                "remove_existing" => "yes"
            ]);
            break;
    }


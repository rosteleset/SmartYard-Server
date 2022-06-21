<?php

    // extensions.lua helper

    echo json_encode([
        "a" => "b",
    ]);

    $_RAW = json_decode(file_get_contents("php://input"), true);

    error_log(print_r($_RAW, true));
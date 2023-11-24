<?php
    mb_internal_encoding("UTF-8");
    require_once "Kamailio/Kamailio.php";
    require_once "utils/error.php";
    require_once "utils/loader.php";
    require_once "utils/checkint.php";
    require_once "utils/db_ext.php";
    require_once "utils/api_exec.php";
    require_once "utils/api_respone.php";
    require_once "backends/backend.php";

    use Kamailio\Kamailio;

    // get global configuration
    $config = loadConfiguration();

    // DB connection
    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (Exception $err) {
        response(500, [
                "Can't open database " . $config["db"]["dsn"],
                $err->getMessage(),
            ],
        );
        exit(1);
    }

    // Usage
    $kamailioAuthHandler = new Kamailio();
    $kamailioAuthHandler->handleRequest();
<?php

    require_once 'vendor/autoload.php';

    mb_internal_encoding("UTF-8");

    require_once "kamailio/kamailio.php";
    require_once "utils/functions.php";
    require_once "utils/error.php";
    require_once "utils/loader.php";
    require_once "utils/PDOExt.php";
    require_once "utils/apiExec.php";
    require_once "utils/apiResponse.php";
    require_once "backends/backend.php";
    require_once "utils/i18n.php";

    use kamailio\kamailio;

    // get global configuration
    try {
        $config = loadConfiguration();
        if ($config instanceof \Exception){
            throw new \Exception ($config->getMessage());
        }
    } catch (\Exception $err) {
        response(555, false, false, $err->getMessage());
        exit(1);
    }

    try {
        $db = new PDOExt(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
    } catch (\Exception $err) {
        response(500, false, false, [
            "Can't open database " . $config["db"]["dsn"],
            $err->getMessage(),
        ]);
        exit(1);
    }

    if (@$config["db"]["schema"]) {
        $db->exec("SET search_path TO " . $config["db"]["schema"] . ", public");
    }

    try {
        $kamailioAuthHandler = new Kamailio();
        $kamailioAuthHandler->handleRequest();
    } catch (\Exception $err) {
        response(500, false, false, "Failed to handle request");
        exit(1);
    }
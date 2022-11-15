<?php

    // API for internal usage
    require_once __DIR__ . "/backends/backend.php";
    require_once __DIR__ . "/utils/loader.php";
    require_once __DIR__ . "/utils/db_ext.php";
    require_once __DIR__ . "/internal/services/router.php";
    require_once __DIR__ . "/internal/routes/routes.php";
    require_once __DIR__ . "/internal/services/response.php";
    require_once __DIR__ . "/internal/actions/actions.php";
    require_once __DIR__ . "/internal/services/access.php";

    use internal\services\Router;
    use internal\services\Response;

    $config = false;

    try {
        $config = @json_decode(file_get_contents(__DIR__ . "/config/config.json"), true);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        Response::res(500, "Internal error", "Config not found");
        exit();
    }

    if (!$config) {
        Response::res(500, "Internal error", "Config not found");
        exit();
    }

    try {
        $db = new PDO_EXT(@$config["db"]["dsn"], @$config["db"]["username"], @$config["db"]["password"], @$config["db"]["options"]);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        error_log(print_r($e, true));
        Response::res(555, "Error", [
            "error" => "PDO",
        ]);
    }

    Router::run();